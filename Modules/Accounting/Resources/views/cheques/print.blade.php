<style>
    @media print {
        .no-print { display: none; }
        @page { size: landscape; margin: 0; }
    }
    .cheque-container {
        position: relative;
        width: 180mm; /* عرض الشيك التقريبي */
        height: 80mm;  /* طول الشيك التقريبي */
        font-family: 'Arial';
    }
    .cheque-date { position: absolute; top: 10mm; left: 140mm; letter-spacing: 5px; }
    .cheque-name { position: absolute; top: 25mm; left: 40mm; font-weight: bold; }
    .cheque-amount-text { position: absolute; top: 35mm; left: 30mm; width: 100mm; }
    .cheque-amount-num { position: absolute; top: 35mm; left: 145mm; font-weight: bold; }
</style>

<div class="cheque-container">
    <div class="cheque-date">{{ \Carbon\Carbon::parse($cheque->cheque_return_date)->format('d m Y') }}</div>
    <div class="cheque-name">{{ $cheque->contact_name }}</div>
    <div class="cheque-amount-text">فقط مائة وخمسون ديناراً لا غير (مثال)</div>
    <div class="cheque-amount-num">#{{ number_format($cheque->amount, 2) }}#</div>
</div>

<script>
    window.onload = function() { window.print(); }
</script>