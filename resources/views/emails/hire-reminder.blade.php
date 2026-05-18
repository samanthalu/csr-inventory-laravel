<x-mail::message>

@if($type === 'overdue')
# ⚠ Equipment Return Overdue
@elseif($type === 'due_today')
# Equipment Due Back Today
@else
# Equipment Return Reminder
@endif

Dear **{{ $staffName }}**,

@if($type === 'overdue')
Your hired equipment was due back **{{ $days }} day(s) ago** ({{ $returnDate }}). Please return the items to the CSR office as soon as possible to avoid further escalation.
@elseif($type === 'due_today')
This is a reminder that your hired equipment is due back **today ({{ $returnDate }})**. Please ensure the items are returned to the CSR office by end of business.
@else
This is a friendly reminder that your hired equipment is due back in **{{ $days }} day(s)** on **{{ $returnDate }}**.
@endif

**Hired Items:**

@foreach($items as $item)
- {{ $item }}
@endforeach

If you need an extension, please contact the CSR office before the due date.

<x-mail::button :url="''" :color="$type === 'overdue' ? 'red' : 'blue'">
Contact CSR Office
</x-mail::button>

Thank you,<br>
**CSR — University of Malawi**

</x-mail::message>
