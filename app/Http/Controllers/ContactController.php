<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254'],
            'message' => ['required', 'string', 'max:5000'],
            'uses_platform' => ['required', 'in:yes,no'],
        ]);
        $recipients = array_values(array_filter([config('mail.contact_address')]));
        abort_unless(count($recipients), 503, 'O contato está temporariamente indisponível.');
        $usesPlatform = $data['uses_platform'] === 'yes' ? 'Sim' : 'Não';
        $body = "Nome: {$data['name']}\nE-mail: {$data['email']}\nJá usa o Escopo Fácil? {$usesPlatform}\n\n{$data['message']}";
        Mail::raw($body, function ($mail) use ($data, $recipients) {
            $mail->to($recipients)->replyTo($data['email'], $data['name'])->subject('Contato — Escopo Fácil');
        });
        $message = config('mail.default') === 'log'
            ? 'Mensagem registrada no ambiente local de testes. Nenhum e-mail externo foi enviado.'
            : 'Mensagem enviada! Nossa equipe responderá pelo e-mail informado.';
        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : redirect('/#contato')->with('contact_status', $message);
    }
}
