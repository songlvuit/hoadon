<?php

namespace App\Http\Controllers;

use App\Enums\DraftStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Trang khách hàng mở bằng link riêng để xem và xác nhận hoá đơn nháp. */
class CustomerInvoiceController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $invoice = $this->find($token);
        $invoice->load('items');

        $seenKey = 'seen_invoice_'.$invoice->id;
        if (! $request->session()->has($seenKey)) {
            $request->session()->put($seenKey, true);
            $invoice->log('draft_viewed', null, 'Khách', true, $request->ip());
        }

        return view('customer.invoice', [
            'invoice' => $invoice,
            'files' => $invoice->draftFileList(),
            'canRespond' => $invoice->draft_status === DraftStatus::Pending,
        ]);
    }

    public function draftFile(string $token, int $index): StreamedResponse
    {
        $invoice = $this->find($token);
        $file = $invoice->draftFileList()[$index] ?? abort(404);

        abort_unless(Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], $file['name']);
    }

    public function finalFile(string $token): StreamedResponse
    {
        $invoice = $this->find($token);
        abort_unless($invoice->final_file && Storage::disk('local')->exists($invoice->final_file), 404);

        return Storage::disk('local')->download($invoice->final_file, $invoice->final_file_name ?: basename($invoice->final_file));
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $invoice = $this->find($token);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'agree' => ['accepted'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên người xác nhận.',
            'agree.accepted' => 'Vui lòng đánh dấu ô đã kiểm tra thông tin.',
        ]);

        if ($invoice->draft_status !== DraftStatus::Pending) {
            return back()->with('error', 'Hoá đơn nháp này hiện không ở trạng thái chờ xác nhận.');
        }

        $invoice->confirmDraft($data['name'], true, $request->ip());
        $this->notifyStaff($invoice, 'Khách đã xác nhận nháp', $data['name'].' xác nhận hoá đơn nháp '.$invoice->code, 'success');

        return back()->with('success', 'Cảm ơn anh/chị. Xác nhận đã được gửi tới bên bán.');
    }

    public function revise(Request $request, string $token): RedirectResponse
    {
        $invoice = $this->find($token);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'feedback' => ['required', 'string', 'max:5000'],
        ], [
            'name.required' => 'Vui lòng nhập họ tên người yêu cầu.',
            'feedback.required' => 'Vui lòng ghi rõ nội dung cần sửa.',
        ]);

        if ($invoice->draft_status !== DraftStatus::Pending) {
            return back()->with('error', 'Hoá đơn nháp này hiện không ở trạng thái chờ xác nhận.');
        }

        $invoice->requestRevision($data['name'], $data['feedback'], true, $request->ip());
        $this->notifyStaff($invoice, 'Khách yêu cầu sửa nháp', $data['name'].' — '.str($data['feedback'])->limit(120), 'danger');

        return back()->with('success', 'Đã gửi yêu cầu sửa. Bên bán sẽ gửi lại nháp mới qua link này.');
    }

    protected function find(string $token): Invoice
    {
        return Invoice::where('public_token', $token)->firstOrFail();
    }

    protected function notifyStaff(Invoice $invoice, string $title, string $body, string $status): void
    {
        $users = User::where('is_active', true)->get();
        if ($users->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($title.' · '.$invoice->code)
            ->body($body)
            ->status($status)
            ->actions([
                Action::make('open')
                    ->label('Mở hồ sơ')
                    ->url(InvoiceResource::getUrl('edit', ['record' => $invoice], panel: 'admin'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($users);
    }
}
