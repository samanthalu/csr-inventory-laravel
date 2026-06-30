<x-mail::message>
# Equipment Hire Invoice

Dear **{{ $staffName }}**,

Please find attached the invoice for your equipment hire (Hire #{{ $hireId }}) from the Center for Social Research.

**Invoice No:** {{ $invoiceNumber }}
**Total Due:** MWK {{ number_format($total, 2) }}

The invoice is attached to this email as a PDF. If you have any questions about this invoice, please contact the CSR office.

Thank you,<br>
**CSR — University of Malawi**

</x-mail::message>
