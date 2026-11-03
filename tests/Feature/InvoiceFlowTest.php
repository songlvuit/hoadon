<?php

namespace Tests\Feature;

use App\Enums\DraftStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInvoice(): Invoice
    {
        $invoice = Invoice::create([
            'company_name' => 'Công ty TNHH Ánh Dương',
            'tax_code' => '0312345678',
            'address' => '12 Võ Văn Ngân, Thủ Đức, TP.HCM',
            'vat_rate' => 8,
        ]);
        $invoice->items()->create(['description' => 'Chụp ảnh sự kiện', 'quantity' => 2, 'unit' => 'buổi', 'unit_price' => 3000000]);
        $invoice->items()->create(['description' => 'Dựng video', 'quantity' => 1, 'unit' => 'gói', 'unit_price' => 4000000]);

        return $invoice->refresh();
    }

    public function test_totals_are_calculated_from_items(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertSame(10000000, $invoice->subtotal);
        $this->assertSame(800000, $invoice->vat_amount);
        $this->assertSame(10800000, $invoice->total);
        $this->assertMatchesRegularExpression('/^HD-\d{4}-001$/', $invoice->code);
    }

    public function test_new_draft_sets_pending_and_customer_can_confirm(): void
    {
        $invoice = $this->makeInvoice();
        $invoice->update(['draft_files' => ['invoices/drafts/a.pdf'], 'draft_file_names' => ['invoices/drafts/a.pdf' => 'nhap.pdf']]);
        $this->assertSame(DraftStatus::Pending, $invoice->refresh()->draft_status);

        $this->get(route('customer.show', $invoice->public_token))
            ->assertOk()
            ->assertSee('Công ty TNHH Ánh Dương')
            ->assertSee('Mười triệu tám trăm nghìn đồng');

        $this->post(route('customer.confirm', $invoice->public_token), ['name' => 'Nguyễn Văn A', 'agree' => '1'])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(DraftStatus::Confirmed, $invoice->draft_status);
        $this->assertSame('Nguyễn Văn A', $invoice->draft_confirmed_by);

        // Tải nháp mới -> phải xác nhận lại
        $invoice->update(['draft_files' => ['invoices/drafts/a.pdf', 'invoices/drafts/b.pdf']]);
        $this->assertSame(DraftStatus::Pending, $invoice->refresh()->draft_status);
        $this->assertNull($invoice->draft_confirmed_by);
    }

    public function test_customer_can_request_revision_and_staff_is_notified(): void
    {
        User::create(['name' => 'Kế toán', 'email' => 'kt@example.com', 'password' => 'secret123']);
        $invoice = $this->makeInvoice();
        $invoice->update(['draft_files' => ['invoices/drafts/a.pdf']]);

        $this->post(route('customer.revise', $invoice->public_token), ['name' => 'Chị B', 'feedback' => 'Sửa địa chỉ'])
            ->assertRedirect();

        $this->assertSame(DraftStatus::Revise, $invoice->refresh()->draft_status);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_payments_update_status(): void
    {
        $invoice = $this->makeInvoice();

        $invoice->payments()->create(['paid_at' => now(), 'amount' => 5000000]);
        $this->assertSame(PaymentStatus::Partial, $invoice->refresh()->payment_status);
        $this->assertSame(5800000, $invoice->remaining());

        $invoice->payments()->create(['paid_at' => now(), 'amount' => 5800000]);
        $this->assertSame(PaymentStatus::Paid, $invoice->refresh()->payment_status);
    }

    public function test_unknown_link_returns_404(): void
    {
        $this->get('/xac-nhan/'.str_repeat('x', 40))->assertNotFound();
    }

    public function test_admin_can_open_panel(): void
    {
        $user = User::create(['name' => 'Admin', 'email' => 'a@example.com', 'password' => 'secret123']);
        $this->makeInvoice();

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin/invoices')->assertOk()->assertSee('Ánh Dương');
        $this->actingAs($user)->get('/admin/invoices/create')->assertOk();
        $this->actingAs($user)->get('/admin/invoices/1/edit')->assertOk();
    }
}
