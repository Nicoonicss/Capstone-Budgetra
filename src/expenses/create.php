<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/currencies.php';

require_login();

$user_id = $_SESSION['user_id'];
$trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;

// Currency
$cur_symbol = $_SESSION['user_currency_symbol'] ?? '$';
$cur_rate   = (float)($_SESSION['user_currency_rate'] ?? 1.0);

// Fallback: reload from DB if session missing
if (empty($_SESSION['user_currency_rate'])) {
    $user_country = $_SESSION['user_country'] ?? '';
    $ci = get_currency_for_country($user_country);
    $cur_rate   = $ci['rate'];
    $cur_symbol = $ci['symbol'];
    $_SESSION['user_currency_rate']   = $cur_rate;
    $_SESSION['user_currency_symbol'] = $cur_symbol;
}

// User's trips
$stmt = $pdo->prepare("SELECT * FROM trips WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$trips = $stmt->fetchAll();

// Recent scans (stored in USD, display in local currency)
$stmt = $pdo->prepare(
    "SELECT e.*, t.destination FROM expenses e
     LEFT JOIN trips t ON e.trip_id = t.id
     WHERE t.user_id = ?
     ORDER BY e.created_at DESC LIMIT 5"
);
$stmt->execute([$user_id]);
$recent_scans = $stmt->fetchAll();

// Active savings goal
$stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$savings_goal = $stmt->fetch();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id_post = intval($_POST['trip_id']);
    // User enters amount in LOCAL currency; divide by rate to store USD
    $amount_local = floatval($_POST['amount']);
    $amount_usd   = $cur_rate > 0 ? round($amount_local / $cur_rate, 4) : $amount_local;
    $category     = trim($_POST['category'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $expense_date = trim($_POST['expense_date'] ?? '');

    if (empty($trip_id_post))  $errors[] = "Trip is required.";
    if ($amount_local <= 0)    $errors[] = "A valid amount is required.";
    if (empty($category))      $errors[] = "Category is required.";
    if (empty($expense_date))  $errors[] = "Date is required.";

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO expenses (trip_id, user_id, amount, category, description, expense_date)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$trip_id_post, $user_id, $amount_usd, $category, $description, $expense_date]);

            // Update trip_budgets actual_spent (also in USD)
            $cat_map = [
                'Transportation'     => 'Transportation',
                'Accommodation'      => 'Accommodation',
                'Food'               => 'Food',
                'Activities'         => 'Tourist Attractions',
                'Shopping'           => 'Shopping',
                'Emergency Expenses' => 'Emergency Funds',
            ];
            $bcat = $cat_map[$category] ?? 'Emergency Funds';
            $stmt = $pdo->prepare(
                "UPDATE trip_budgets SET actual_spent = actual_spent + ? WHERE trip_id = ? AND category = ?"
            );
            $stmt->execute([$amount_usd, $trip_id_post, $bcat]);

            // Per-category budget notifications
            $stmt = $pdo->prepare("SELECT destination, end_date FROM trips WHERE id = ?");
            $stmt->execute([$trip_id_post]);
            $trip_row = $stmt->fetch();
            $dest = $trip_row['destination'] ?? '';

            $stmt = $pdo->prepare(
                "SELECT estimated_cost, actual_spent FROM trip_budgets WHERE trip_id = ? AND category = ?"
            );
            $stmt->execute([$trip_id_post, $bcat]);
            $budget_row = $stmt->fetch();

            if ($budget_row && (float)$budget_row['estimated_cost'] > 0) {
                $budget_cat = (float)$budget_row['estimated_cost'];
                $spent_cat  = (float)$budget_row['actual_spent'];
                $pct_cat    = ($spent_cat / $budget_cat) * 100;

                // Replace any previous threshold notification for this category
                $pdo->prepare(
                    "DELETE FROM notifications WHERE trip_id = ? AND type IN (?, ?, ?)"
                )->execute([
                    $trip_id_post,
                    'cat_exceeded_' . $bcat,
                    'cat_warning_'  . $bcat,
                    'cat_success_'  . $bcat,
                ]);

                $cat_type = $cat_msg = '';
                if ($pct_cat >= 100) {
                    $over     = format_currency(($spent_cat - $budget_cat) * $cur_rate, $cur_symbol);
                    $cat_type = 'cat_exceeded_' . $bcat;
                    $cat_msg  = $bcat . ' budget exceeded — you\'re ' . $over . ' over for ' . $dest . '.';
                } elseif ($pct_cat >= 80) {
                    $left     = format_currency(($budget_cat - $spent_cat) * $cur_rate, $cur_symbol);
                    $cat_type = 'cat_warning_' . $bcat;
                    $cat_msg  = $bcat . ' at ' . round($pct_cat) . '% — ' . $left . ' remaining for ' . $dest . '.';
                } elseif ($pct_cat > 0 && $pct_cat <= 30) {
                    $cat_type = 'cat_success_' . $bcat;
                    $cat_msg  = 'Great progress on ' . $bcat . '! Only ' . round($pct_cat) . '% used for ' . $dest . '.';
                }
                if ($cat_type) {
                    $pdo->prepare(
                        "INSERT INTO notifications (user_id, trip_id, type, message) VALUES (?, ?, ?, ?)"
                    )->execute([$user_id, $trip_id_post, $cat_type, $cat_msg]);
                }
            }

            // Trip-ending-soon notification (insert once per trip)
            if (!empty($trip_row['end_date'])) {
                $days_left = (int)ceil((strtotime($trip_row['end_date']) - time()) / 86400);
                if ($days_left > 0 && $days_left <= 3) {
                    $chk = $pdo->prepare(
                        "SELECT id FROM notifications WHERE trip_id = ? AND type = 'trip_ending' LIMIT 1"
                    );
                    $chk->execute([$trip_id_post]);
                    if (!$chk->fetch()) {
                        $pdo->prepare(
                            "INSERT INTO notifications (user_id, trip_id, type, message) VALUES (?, ?, ?, ?)"
                        )->execute([
                            $user_id, $trip_id_post, 'trip_ending',
                            'Your trip to ' . $dest . ' ends in ' . $days_left . ' day' . ($days_left > 1 ? 's' : '') . '. Review your budget!',
                        ]);
                    }
                }
            }

            $pdo->commit();
            $success          = true;
            $trip_id_for_link = $trip_id_post;

            // Refresh recent scans after save
            $stmt = $pdo->prepare(
                "SELECT e.*, t.destination FROM expenses e
                 LEFT JOIN trips t ON e.trip_id = t.id
                 WHERE t.user_id = ?
                 ORDER BY e.created_at DESC LIMIT 5"
            );
            $stmt->execute([$user_id]);
            $recent_scans = $stmt->fetchAll();
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Failed to save expense. Please try again.";
        }
    }
}

$body_class = 'dashboard-body';
$active_sidebar = 'reports';
?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<div class="dashboard-wrapper">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="dash-main">
    <div class="app-content">
        <div class="scan-page-grid">

            <!-- ── LEFT: Upload + Form ───────────────────── -->
            <div>
                <h1 style="font-size:26px;font-weight:800;margin-bottom:6px;">Scan Your Receipt</h1>
                <p style="color:var(--muted);font-size:14px;margin-bottom:24px;">
                    Upload a photo of your receipt to automatically extract and track expenses.
                </p>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Expense saved!
                        <a href="<?php echo BASE_URL; ?>/src/expenses/index.php?trip_id=<?php echo $trip_id_for_link ?? 0; ?>"
                           style="font-weight:600;">View all expenses →</a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e): echo htmlspecialchars($e) . '<br>'; endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Dropzone -->
                <div class="dropzone" id="dropzone">
                    <input type="file" id="fileInput" accept="image/*,application/pdf" style="display:none;">
                    <div id="dropzoneContent">
                        <div class="dropzone-icon"><i class="fa-solid fa-camera"></i></div>
                        <div class="dropzone-title">Drag &amp; Drop Receipt</div>
                        <div class="dropzone-sub">or click to browse from your computer</div>
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="document.getElementById('fileInput').click();">
                            <i class="fa-solid fa-upload" style="font-size:12px;"></i> Select File
                        </button>
                    </div>

                    <!-- OCR loading state (hidden by default) -->
                    <div id="ocrLoading" style="display:none;flex-direction:column;align-items:center;gap:12px;padding:20px 0;">
                        <div class="ocr-spinner"></div>
                        <div style="font-size:14px;font-weight:600;color:var(--text);">Scanning receipt…</div>
                        <div style="font-size:12px;color:var(--muted);" id="ocrStatus">Extracting text with OCR…</div>
                    </div>

                    <!-- Preview (hidden by default) -->
                    <div class="dropzone-preview" id="preview" style="display:none;">
                        <img id="previewImg" src="" alt="Receipt preview"
                             style="max-width:100%;max-height:200px;border-radius:8px;object-fit:contain;">
                        <p style="font-size:12px;color:var(--muted);margin-top:8px;" id="fileName"></p>
                    </div>
                </div>

                <!-- OCR result badge -->
                <div id="ocrResult" style="display:none;margin-top:10px;">
                    <div style="background:#F0FDF4;border:1px solid #86EFAC;border-radius:8px;padding:10px 14px;
                                display:flex;align-items:center;gap:8px;font-size:13px;">
                        <i class="fa-solid fa-circle-check" style="color:#16A34A;"></i>
                        <span>OCR complete — form auto-filled with <strong id="ocrConfidence"></strong>% confidence.</span>
                    </div>
                </div>

                <!-- Expense Details Form -->
                <div class="expense-form-card card" style="margin-top:20px;">
                    <div style="padding:20px 24px 8px;">
                        <h3 style="font-size:16px;font-weight:700;margin-bottom:4px;">Expense Details</h3>
                        <p style="font-size:13px;color:var(--muted);">Fill in details below or let OCR auto-fill after scanning.</p>
                    </div>
                    <form method="POST" id="expenseForm" style="padding:16px 24px 24px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <!-- Trip -->
                            <div class="form-group">
                                <label class="form-label">Trip *</label>
                                <select name="trip_id" id="tripSelect" class="form-control" required>
                                    <option value="">Select trip</option>
                                    <?php foreach ($trips as $t): ?>
                                        <option value="<?php echo $t['id']; ?>"
                                            <?php echo $trip_id == $t['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['destination']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Amount (user enters in LOCAL currency) -->
                            <div class="form-group">
                                <label class="form-label">Amount * <small style="color:var(--muted);font-weight:400;">(in <?php echo htmlspecialchars($cur_symbol); ?>)</small></label>
                                <div class="input-wrapper">
                                    <span class="input-icon" style="font-weight:700;font-size:13px;"><?php echo htmlspecialchars($cur_symbol); ?></span>
                                    <input type="number" name="amount" id="amountInput"
                                           class="form-control" step="0.01" min="0.01"
                                           placeholder="0.00" required
                                           value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
                                </div>
                            </div>
                            <!-- Category -->
                            <div class="form-group">
                                <label class="form-label">Category *</label>
                                <select name="category" id="categorySelect" class="form-control" required>
                                    <option value="">Select category</option>
                                    <option value="Food">Food &amp; Dining</option>
                                    <option value="Transportation">Transportation</option>
                                    <option value="Accommodation">Accommodation</option>
                                    <option value="Activities">Activities &amp; Attractions</option>
                                    <option value="Shopping">Shopping</option>
                                    <option value="Emergency Expenses">Emergency Expenses</option>
                                </select>
                            </div>
                            <!-- Date -->
                            <div class="form-group">
                                <label class="form-label">Date *</label>
                                <input type="date" name="expense_date" id="dateInput" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>"
                                       required>
                            </div>
                        </div>
                        <!-- Description -->
                        <div class="form-group">
                            <label class="form-label">Description / Merchant</label>
                            <input type="text" name="description" id="descInput" class="form-control"
                                   placeholder="e.g. L'Osteria Pizza"
                                   value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px;">
                            <i class="fa-solid fa-floppy-disk"></i> Save Expense
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── RIGHT: Sidebar ─────────────────────── -->
            <div>
                <!-- On the go card -->
                <div class="scan-card-blue" style="margin-bottom:16px;">
                    <div>
                        <div style="font-size:14px;font-weight:700;color:#fff;">On the go?</div>
                        <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:2px;">Use mobile camera</div>
                    </div>
                    <div class="scan-card-blue-icon">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                </div>

                <!-- Recent Scans -->
                <div class="scan-sidebar-card">
                    <div class="scan-header">
                        <span class="scan-header-title">Recent Scans</span>
                        <a href="<?php echo BASE_URL; ?>/src/expenses/index.php" class="scan-header-link">View All</a>
                    </div>

                    <?php
                    $scan_icons = [
                        'Food'               => 'fa-utensils',
                        'Transportation'     => 'fa-train',
                        'Shopping'           => 'fa-bag-shopping',
                        'Accommodation'      => 'fa-bed',
                        'Activities'         => 'fa-star',
                        'Emergency Expenses' => 'fa-shield-halved',
                    ];
                    if (!empty($recent_scans)):
                        foreach ($recent_scans as $i => $scan):
                            $ic  = $scan_icons[$scan['category']] ?? 'fa-receipt';
                            // Amount stored in USD; display in user's local currency
                            $disp_amt = format_currency((float)$scan['amount'] * $cur_rate, $cur_symbol);
                    ?>
                        <div class="scan-item">
                            <div class="scan-item-icon">
                                <i class="fa-solid <?php echo $ic; ?>"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div class="scan-item-name">
                                    <?php echo htmlspecialchars(substr($scan['description'] ?: $scan['category'], 0, 22)); ?>
                                </div>
                                <div class="scan-item-date">
                                    <?php echo date('M d, Y', strtotime($scan['expense_date'] ?: $scan['created_at'])); ?>
                                    <?php if ($scan['destination']): ?>
                                        · <?php echo htmlspecialchars($scan['destination']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="scan-item-right">
                                <div class="scan-item-amt"><?php echo $disp_amt; ?></div>
                                <span class="badge-verified">VERIFIED</span>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div style="padding:24px 0;text-align:center;color:var(--muted);font-size:13px;">
                            No expenses yet. Add one above.
                        </div>
                    <?php endif; ?>

                    <div class="scan-tags">
                        <span class="scan-tag">#Travel</span>
                        <span class="scan-tag">#Budget</span>
                        <span class="scan-tag">#Expenses</span>
                    </div>
                </div>

                <!-- Savings Goal card -->
                <?php if ($savings_goal): ?>
                    <?php $pct_saved = $savings_goal['target_amount'] > 0
                        ? min(100, ($savings_goal['current_savings'] / $savings_goal['target_amount']) * 100) : 0; ?>
                    <div class="savings-mini-card">
                        <div class="savings-mini-icon"><i class="fa-solid fa-piggy-bank"></i></div>
                        <div class="savings-mini-name"><?php echo htmlspecialchars($savings_goal['goal_name']); ?></div>
                        <div class="progress" style="margin-top:10px;margin-bottom:6px;">
                            <div class="progress-bar" style="width:<?php echo $pct_saved; ?>%;background:var(--dark);"></div>
                        </div>
                        <div class="savings-mini-sub">
                            <?php echo format_currency($savings_goal['current_savings'] * $cur_rate, $cur_symbol); ?> of
                            <?php echo format_currency($savings_goal['target_amount'] * $cur_rate, $cur_symbol); ?> saved
                        </div>
                    </div>
                <?php else: ?>
                    <div class="savings-mini-card">
                        <div class="savings-mini-icon"><i class="fa-solid fa-piggy-bank"></i></div>
                        <div class="savings-mini-name">No savings goal yet</div>
                        <div style="font-size:12px;color:var(--muted);margin-top:8px;">
                            <a href="<?php echo BASE_URL; ?>/src/savings/index.php" style="color:var(--primary);">Create a savings goal →</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /scan-page-grid -->
    </div><!-- /app-content -->
    </div><!-- /dash-main -->
</div><!-- /dashboard-wrapper -->

<style>
.ocr-spinner {
    width: 36px; height: 36px;
    border: 3px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.75s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.dropzone.ocr-active { border-color: var(--primary); background: #FDF0E8; }

.form-highlight {
    animation: highlightFade 1.2s ease;
}
@keyframes highlightFade {
    0%   { background: #FEF9C3; }
    100% { background: transparent; }
}
</style>

<script>
var OCR_URL    = '<?php echo BASE_URL; ?>/src/expenses/ocr.php';
var dz         = document.getElementById('dropzone');
var fi         = document.getElementById('fileInput');
var dzContent  = document.getElementById('dropzoneContent');
var ocrLoading = document.getElementById('ocrLoading');
var ocrStatus  = document.getElementById('ocrStatus');
var previewDiv = document.getElementById('preview');

// ── Drag & Drop ──────────────────────────────────────────────────────────
dz.addEventListener('dragover',  function(e) { e.preventDefault(); dz.classList.add('drag-over'); });
dz.addEventListener('dragleave', function()  { dz.classList.remove('drag-over'); });
dz.addEventListener('drop', function(e) {
    e.preventDefault();
    dz.classList.remove('drag-over');
    var file = e.dataTransfer.files[0];
    if (file) processFile(file);
});
fi.addEventListener('change', function() {
    if (fi.files[0]) processFile(fi.files[0]);
});
dz.addEventListener('click', function(e) {
    if (e.target.tagName === 'BUTTON' || e.target.tagName === 'I') return;
    if (!ocrLoading.style.display || ocrLoading.style.display === 'none') {
        fi.click();
    }
});

function processFile(file) {
    if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
        alert('Please upload an image or PDF file.');
        return;
    }

    // Show image preview
    if (file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('fileName').textContent = file.name;
            previewDiv.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    // Start OCR
    runOCR(file);
}

var statusMessages = [
    'Uploading receipt…',
    'Detecting text regions…',
    'Extracting line items…',
    'Identifying merchant…',
    'Calculating totals…',
    'Finalizing results…',
];

function runOCR(file) {
    // Show loading state
    dzContent.style.display = 'none';
    ocrLoading.style.display = 'flex';
    dz.classList.add('ocr-active');
    document.getElementById('ocrResult').style.display = 'none';

    // Cycle through status messages
    var msgIdx = 0;
    var msgInterval = setInterval(function() {
        ocrStatus.textContent = statusMessages[msgIdx % statusMessages.length];
        msgIdx++;
    }, 400);

    // Send to OCR endpoint
    var fd = new FormData();
    fd.append('receipt', file);

    fetch(OCR_URL, { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            clearInterval(msgInterval);
            ocrLoading.style.display = 'none';
            dz.classList.remove('ocr-active');

            if (data.success) {
                autofillForm(data);
            } else {
                dzContent.style.display = 'block';
                alert('OCR failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(function() {
            clearInterval(msgInterval);
            ocrLoading.style.display = 'none';
            dzContent.style.display = 'block';
            dz.classList.remove('ocr-active');
            alert('Could not reach OCR service. Please fill the form manually.');
        });
}

function autofillForm(data) {
    // Amount
    if (data.amount) {
        var amt = document.getElementById('amountInput');
        amt.value = data.amount;
        flashField(amt);
    }

    // Description
    if (data.description) {
        var desc = document.getElementById('descInput');
        desc.value = data.description;
        flashField(desc);
    }

    // Category
    if (data.category) {
        var cat = document.getElementById('categorySelect');
        for (var i = 0; i < cat.options.length; i++) {
            if (cat.options[i].value === data.category) {
                cat.selectedIndex = i;
                flashField(cat);
                break;
            }
        }
    }

    // Date
    if (data.date) {
        var dt = document.getElementById('dateInput');
        dt.value = data.date;
        flashField(dt);
    }

    // Show confidence badge
    document.getElementById('ocrConfidence').textContent = data.confidence || 92;
    document.getElementById('ocrResult').style.display = 'block';
}

function flashField(el) {
    el.classList.remove('form-highlight');
    void el.offsetWidth; // reflow
    el.classList.add('form-highlight');
}
</script>

</body>
</html>
