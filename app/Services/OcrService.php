<?php
namespace App\Services;

use App\Models\OcrLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OcrService
{
    public function scan(UploadedFile $file, int $userId): array
    {
        $path     = $file->store('receipts', 'public');
        $filename = basename($path);
        $apiKey   = config('services.ocr.key');

        if (empty($apiKey)) {
            OcrLog::create([
                'user_id'       => $userId,
                'filename'      => $filename,
                'status'        => 'failed',
                'error_message' => 'OCR API key not configured.',
            ]);
            return ['amount' => null, 'date' => null, 'description' => null, 'confidence' => 0];
        }

        $imageContent = Storage::disk('public')->get($path);
        $base64       = 'data:' . $file->getMimeType() . ';base64,' . base64_encode($imageContent);

        $response = Http::asForm()->post(config('services.ocr.endpoint'), [
            'apikey'            => $apiKey,
            'base64Image'       => $base64,
            'isOverlayRequired' => 'false',
            'detectOrientation' => 'true',
            'scale'             => 'true',
            'OCREngine'         => '2',
        ]);

        if (!$response->successful() || ($response->json('OCRExitCode') ?? 0) < 1) {
            OcrLog::create([
                'user_id'       => $userId,
                'filename'      => $filename,
                'status'        => 'failed',
                'error_message' => 'OCR API error: ' . $response->status(),
            ]);
            return ['amount' => null, 'date' => null, 'description' => null, 'confidence' => 0];
        }

        $text       = $response->json('ParsedResults.0.ParsedText', '');
        $parsed     = $this->parseReceiptText($text);
        $confidence = $parsed['amount'] ? 85.0 : 30.0;

        OcrLog::create([
            'user_id'    => $userId,
            'filename'   => $filename,
            'status'     => $parsed['amount'] ? 'success' : 'partial',
            'confidence' => $confidence,
        ]);

        return array_merge($parsed, ['confidence' => $confidence]);
    }

    private function parseReceiptText(string $text): array
    {
        $amount = $date = $description = null;

        if (preg_match('/(?:TOTAL|AMOUNT|SUBTOTAL|DUE|GRAND TOTAL)[:\s]*[₱$]?\s*([\d,]+\.?\d{0,2})/i', $text, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
        } elseif (preg_match('/[₱$]\s*([\d,]+\.?\d{0,2})/', $text, $m)) {
            $amount = (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $m)) {
            $date = $m[1];
        } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $m)) {
            $date = "{$m[3]}-" . str_pad($m[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT);
        }

        $lines       = array_filter(array_map('trim', explode("\n", $text)));
        $description = reset($lines) ?: null;

        return compact('amount', 'date', 'description');
    }
}
