@component('mail::message')
# Děkujeme za nákup!
Vaše platba byla úspěšná, kredity byly aktualizovány.<br>

# Detaily
___
### ID Platby:      **{{$payment->id}}**<br>
### Stav:           **{{$payment->status}}**<br>
### Cena:           **{{$payment->formatCurrency()}}**<br>
### Typ:            **{{$payment->type}}**<br>
### Množství:       **{{$payment->amount}}**<br>
### Stav kreditů:   **{{$payment->user->credits}}**<br>
### ID uživatele:   **{{$payment->user_id}}**<br>

<br>
Děkujeme,<br>
{{ config('app.name') }}
@endcomponent
