@component('mail::message')
# {{__('Děkujeme za nákup!')}}
{{__('Vaše platba byla úspěšná, kredity byly aktualizovány.')}}'<br>

# Detaily
___
### {{__('ID Platby')}}':    **{{$payment->id}}**<br>
### {{__('Stav')}}':         **{{$payment->status}}**<br>
### {{__('Cena')}}':         **{{$payment->formatToCurrency($payment->total_price)}}**<br>
### {{__('Typ')}}':          **{{$payment->type}}**<br>
### {{__('Množství')}}':     **{{$payment->amount}}**<br>
### {{__('Stav kreditů')}}':  **{{$payment->user->credits}}**<br>
### {{__('ID uživatele')}}':  **{{$payment->user_id}}**<br>

<br>
{{__('Děkujeme,')}},<br>
{{ config('app.name') }}
@endcomponent
