<?php

use App\Http\Controllers\CompanyAssetController;
use App\Http\Controllers\Finance\PaymentDocumentController;
use App\Http\Controllers\Marketing\Admin\EmailTemplatePreviewController;
use App\Http\Controllers\Marketing\Admin\FormTemplatePreviewController;
use App\Http\Controllers\Marketing\Admin\LandingPagePreviewController;
use App\Http\Controllers\Marketing\Admin\LandingPageUtmUrlController;
use App\Http\Controllers\Marketing\Public\EmailTrackingController;
use App\Http\Controllers\Marketing\Public\LandingPageController;
use App\Http\Controllers\Marketing\Public\UnsubscribeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Sales\QuotationPublicController;
use App\Http\Controllers\Support\SupportInboundEmailController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'welcome']);
Route::get('/company/logo', [CompanyAssetController::class, 'logo'])->name('company.logo');

// ─── Admin Marketing Routes ────────────────────────────────────────────────
Route::middleware(['web', 'auth'])->prefix('admin/marketing')->name('marketing.')->group(function (): void {
    // LP-PRV: Landing Page Preview (admin only, bypasses published check + tracking)
    Route::get('landing-pages/{landingPage}/preview', [LandingPagePreviewController::class, 'preview'])->name('landing-pages.preview');
    Route::delete('landing-pages/{landingPage}/utm-urls/{utmUrl}', [LandingPageUtmUrlController::class, 'destroy'])->name('landing-pages.utm-urls.destroy');
    Route::get('email-templates/{emailTemplate}/preview', [EmailTemplatePreviewController::class, 'preview'])->name('email-templates.preview');
    Route::get(
        'form-templates/{formTemplate}/preview',
        [FormTemplatePreviewController::class, 'preview']
    )->name('form-templates.preview');
});

// ─── Private Finance Documents ─────────────────────────────────────────────
Route::middleware(['web', 'auth'])->prefix('admin/finance')->name('finance.')->group(function (): void {
    Route::get('payment-evidence/{file}', [PaymentDocumentController::class, 'evidence'])->name('payment-evidence');
    Route::get('payment-receipts/{receipt}', [PaymentDocumentController::class, 'receipt'])->name('payment-receipts');
});

// ─── Public Tracking Routes (no auth) ─────────────────────────────────────
Route::get('/m/open/{token}.gif', [EmailTrackingController::class, 'open'])->name('marketing.track.open');
Route::get('/m/click/{token}', [EmailTrackingController::class, 'click'])->name('marketing.track.click');
Route::get('/m/unsubscribe/{token}', [UnsubscribeController::class, 'show'])->name('marketing.unsubscribe.show');
Route::post('/m/unsubscribe/{token}', [UnsubscribeController::class, 'store'])->name('marketing.unsubscribe.store');
Route::get('/m/care/open/{token}.gif', [EmailTrackingController::class, 'openCare'])->name('care.track.open');
Route::get('/m/care/click/{token}', [EmailTrackingController::class, 'clickCare'])->name('care.track.click');

// ─── Public Landing Page Routes ───────────────────────────────────────────
Route::get('/lp/{slug}', [LandingPageController::class, 'show'])->name('marketing.landing-pages.public.show');
Route::post('/lp/{slug}/submit', [LandingPageController::class, 'submit'])->name('marketing.landing-pages.public.submit');
Route::get('/lp/{slug}/thank-you', [LandingPageController::class, 'thankYou'])->name('marketing.landing-pages.public.thank-you');

// ─── Public Quotation Routes ──────────────────────────────────────────────
Route::prefix('q')->name('sales.quotation.public.')->group(function (): void {
    Route::get('/{quotationCode}/{token}', [QuotationPublicController::class, 'show'])->name('show');
    Route::get('/{quotationCode}/{token}/pdf', [QuotationPublicController::class, 'pdf'])->name('pdf');
    Route::get('/{quotationCode}/{token}/receipt', [QuotationPublicController::class, 'receipt'])->name('receipt');
    Route::get('/{quotationCode}/{token}/csrf-token', [QuotationPublicController::class, 'csrfToken'])->name('csrf-token');
    Route::post('/{quotationCode}/{token}/accept', [QuotationPublicController::class, 'accept'])->name('accept');
    Route::post('/{quotationCode}/{token}/reject', [QuotationPublicController::class, 'reject'])->name('reject');
    Route::post('/{quotationCode}/{token}/request-revision', [QuotationPublicController::class, 'requestRevision'])->name('request-revision');
    Route::post('/{quotationCode}/{token}/send-otp', [QuotationPublicController::class, 'sendOtp'])->name('send-otp');
    Route::post('/{quotationCode}/{token}/verify-otp', [QuotationPublicController::class, 'verifyOtp'])->name('verify-otp');
    Route::post('/{quotationCode}/{token}/notify-payment', [QuotationPublicController::class, 'notifyPayment'])->name('notify-payment');
});

// ─── Optional Support Inbound Email Adapter ─────────────────────────────────
// Provider-agnostic webhook. Enable by setting V1_SUPPORT_INBOUND_WEBHOOK_SECRET.
Route::post('/webhooks/support/inbound-email', SupportInboundEmailController::class)
    ->name('support.inbound-email');

// ─── Language Switch ───────────────────────────────────────────────────────
Route::match(['get', 'post'], '/language/switch', [PageController::class, 'languageSwitch'])->name('language.switch');
