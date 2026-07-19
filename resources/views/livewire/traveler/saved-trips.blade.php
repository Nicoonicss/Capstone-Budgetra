<div>

    @if ($this->trips->isEmpty())
    <div class="empty-state-center">
        <div style="width:72px;height:72px;border-radius:20px;background:#F5EDE7;display:flex;align-items:center;justify-content:center;margin-bottom:20px;">
            <i class="fa-solid fa-suitcase-rolling" style="font-size:32px;color:var(--primary);"></i>
        </div>
        <h2 style="font-weight:700;font-size:20px;margin-bottom:8px;color:#1A0A00;">No saved trips yet</h2>
        <p style="color:#9B8EA0;margin-bottom:24px;font-size:14px;max-width:300px;">Start planning your first adventure!</p>
        <a href="{{ route('trips.plan') }}" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-paper-plane"></i> Plan Your First Trip
        </a>
    </div>
    @else
    <div style="display:flex;flex-wrap:wrap;gap:20px;justify-content:center;">
        @foreach ($this->trips as $trip)
        @php
            $dest     = $trip->destination ?? 'Unknown';
            $fromCode = $trip->origin_code ?? 'MNL';
            $toCode   = $trip->destination_code ?? '';
            $tType    = strtoupper($trip->travel_type ?? 'SOLO');
            $dateFrom = $trip->start_date->format('M j');
            $dateTo   = $trip->end_date->format('M j, Y');
            $days     = $trip->days;
            $displayCost = $trip->total_cost ?? $trip->budget_limit ?? 0;
            $cover    = $trip->cover_image;
            $statusColor = match($trip->status) {
                'active'   => '#2E7D32',
                'upcoming' => '#7B3F00',
                default    => '#6B7280',
            };
        @endphp
        <div style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);display:flex;flex-direction:column;width:360px;flex-shrink:0;">
            {{-- Cover image --}}
            <div style="position:relative;height:160px;background:linear-gradient(135deg,#7B3F00,#C8874A);overflow:hidden;">
                @if($cover)
                <img src="{{ $cover }}" alt="{{ $dest }}"
                     style="width:100%;height:100%;object-fit:cover;display:block;"
                     onerror="this.style.display='none'">
                @endif
                <div style="position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,0.15),rgba(0,0,0,0.55));"></div>
                <span style="position:absolute;top:12px;left:12px;background:rgba(255,255,255,0.22);color:#fff;font-size:10px;font-weight:700;letter-spacing:0.5px;padding:3px 10px;border-radius:20px;backdrop-filter:blur(4px);">{{ $tType }}</span>
                <span style="position:absolute;top:12px;right:12px;background:{{ $statusColor }};color:#fff;font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;text-transform:uppercase;">{{ $trip->status }}</span>
                <div style="position:absolute;bottom:10px;left:14px;right:14px;">
                    <div style="font-size:16px;font-weight:700;color:#fff;line-height:1.3;margin-bottom:6px;">
                        {{ $tType === 'SOLO' ? 'Solo Getaway to ' : '' }}{{ $dest }}
                    </div>
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        @if($fromCode)
                        <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:rgba(255,255,255,0.9);">
                            <i class="fa-solid fa-plane" style="font-size:10px;color:#F5C97A;"></i>
                            <span>{{ $fromCode }} to {{ $toCode }}</span>
                        </div>
                        @endif
                        <div style="display:flex;align-items:center;gap:5px;font-size:11px;color:rgba(255,255,255,0.9);">
                            <i class="fa-regular fa-calendar-days" style="font-size:10px;color:#F5C97A;"></i>
                            <span>{{ $dateFrom }} - {{ $dateTo }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div style="padding:16px;">
                <div style="margin-bottom:14px;">
                    <div style="font-size:10px;font-weight:600;color:#9B8EA0;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px;">Total Cost</div>
                    <div style="font-size:20px;font-weight:700;color:#C8874A;">PHP {{ number_format($displayCost, 0) }}</div>
                </div>

                <div style="display:flex;gap:8px;">
                    <button wire:click="showDetail({{ $trip->id }})"
                            style="flex:1.2;background:#7B3F00;color:#fff;border:none;border-radius:10px;padding:10px 8px;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;">
                        View Details
                    </button>
                    <button wire:click="confirmDelete({{ $trip->id }})"
                            style="flex:1;background:transparent;color:#7B3F00;border:1.5px solid #7B3F00;border-radius:10px;padding:10px 6px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:4px;white-space:nowrap;">
                        <i class="fa-regular fa-trash-can" style="font-size:10px;"></i>Delete Trip
                    </button>
                    <a href="{{ route('expenses.index') }}?trip_id={{ $trip->id }}"
                       style="flex:1;background:transparent;color:#7B3F00;border:1.5px solid #7B3F00;border-radius:10px;padding:10px 6px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:4px;white-space:nowrap;">
                        <i class="fa-solid fa-receipt" style="font-size:10px;"></i>Add Expense
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Trip Summary Modal --}}
    @if ($this->detailTrip)
    @php
        $dt      = $this->detailTrip;
        $dtDays  = $dt->days;
        $dtDest  = $dt->destination;
        $dtType  = ucfirst(strtolower($dt->travel_type ?? 'Solo'));
        $dtFrom  = $dt->start_date->format('M j');
        $dtTo    = $dt->end_date->format('M j, Y');
        $dtTotal = $dt->total_cost ?? $dt->budget_limit ?? 0;
        $sd      = $dt->summary_data ?? [];

        $rows = [
            'transportation' => ['label' => 'Transportation',  'icon' => 'fa-solid fa-plane'],
            'accommodation'  => ['label' => 'Accommodation',   'icon' => 'fa-solid fa-bed'],
            'food'           => ['label' => 'Food & Dining',   'icon' => 'fa-solid fa-utensils'],
            'attractions'    => ['label' => 'Attractions',     'icon' => 'fa-solid fa-landmark'],
            'emergency_fund' => ['label' => 'Emergency Fund',  'icon' => 'fa-solid fa-shield-halved'],
        ];
    @endphp
    <div wire:click.self="closeDetail"
         style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#FDFAF7;border-radius:20px;width:100%;max-width:420px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">

            {{-- Modal header --}}
            <div style="padding:20px 22px 16px;">
                <span style="font-size:16px;font-weight:700;color:#1A0A00;">Trip Summary</span>
            </div>

            {{-- Trip title row --}}
            <div style="padding:0 22px 18px;display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:12px;background:#FFF3E0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-plane-departure" style="color:#C8874A;font-size:18px;"></i>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <span style="font-size:15px;font-weight:700;color:#1A0A00;">{{ $dtType }} Getaway to {{ $dtDest }}</span>
                        <span style="background:#FFF3E0;color:#C8874A;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase;">{{ $dtType }}</span>
                    </div>
                </div>
            </div>

            {{-- Summary rows --}}
            <div style="margin:0 16px 16px;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #F0E8DF;">
                <div style="padding:12px 16px 6px;font-size:11px;font-weight:700;color:#9B8EA0;letter-spacing:0.6px;text-transform:uppercase;border-bottom:1px solid #F5EFE8;">Summary</div>
                @if(empty($sd))
                <div style="padding:20px 16px;text-align:center;">
                    <i class="fa-solid fa-circle-info" style="font-size:22px;color:#D4C5B5;margin-bottom:8px;display:block;"></i>
                    <div style="font-size:13px;color:#9B8EA0;">Detailed breakdown not available for this trip.</div>
                </div>
                @else
                @foreach ($rows as $key => $meta)
                @php
                    $rowData   = $sd[$key] ?? null;
                    $rowCost   = $rowData['cost'] ?? 0;
                    $rowDetail = $rowData['detail'] ?? null;
                    if (!$rowCost && !$rowDetail) continue;
                @endphp
                <div style="padding:14px 16px;border-bottom:1px solid #F5EFE8;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;color:#1A0A00;margin-bottom:2px;">{{ $meta['label'] }}</div>
                        @if($rowDetail)
                        <div style="font-size:11px;color:#9B8EA0;line-height:1.4;">{{ $rowDetail }}</div>
                        @endif
                    </div>
                    <div style="font-size:13px;font-weight:600;color:#1A0A00;white-space:nowrap;flex-shrink:0;">
                        PHP {{ number_format($rowCost, 0) }}
                    </div>
                </div>
                @endforeach
                @endif
            </div>

            {{-- Total + Close --}}
            <div style="padding:0 22px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;color:#9B8EA0;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:4px;">Total Cost</div>
                    <div style="font-size:26px;font-weight:800;color:#C8874A;">PHP {{ number_format($dtTotal, 0) }}</div>
                </div>
                <button wire:click="closeDetail"
                        style="background:#7B3F00;color:#fff;border:none;border-radius:10px;padding:10px 22px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if ($deleteTripId)
    <div style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2100;display:flex;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:20px;width:100%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,0.2);overflow:hidden;">
            {{-- Icon header --}}
            <div style="background:#FEF2F2;padding:28px 24px 20px;text-align:center;">
                <div style="width:52px;height:52px;border-radius:50%;background:#FEE2E2;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                    <i class="fa-solid fa-trash-can" style="font-size:22px;color:#DC2626;"></i>
                </div>
                <div style="font-size:17px;font-weight:700;color:#1A0A00;margin-bottom:6px;">Delete Trip?</div>
                <div style="font-size:13px;color:#6B7280;line-height:1.5;">
                    <strong>{{ $deleteTripName }} trip</strong> will be permanently deleted.<br>This action cannot be undone.
                </div>
            </div>
            {{-- Actions --}}
            <div style="display:flex;gap:10px;padding:18px 20px;">
                <button wire:click="cancelDelete"
                        style="flex:1;background:transparent;color:#6B7280;border:1.5px solid #E5E7EB;border-radius:10px;padding:11px 0;font-size:13px;font-weight:600;cursor:pointer;">
                    Cancel
                </button>
                <button wire:click="deleteTrip"
                        style="flex:1;background:#DC2626;color:#fff;border:none;border-radius:10px;padding:11px 0;font-size:13px;font-weight:600;cursor:pointer;">
                    <span wire:loading.remove wire:target="deleteTrip">Delete</span>
                    <span wire:loading wire:target="deleteTrip"><i class="fa-solid fa-spinner fa-spin"></i></span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
