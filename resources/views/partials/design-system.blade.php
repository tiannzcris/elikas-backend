{{-- The E-LIKAS design system for every staff-facing page: the dashboard
    layout (layouts/app) and the login page both include this, so their
    tokens and component classes can't drift apart. The written source of
    truth for every value here is docs/design-system.md. --}}
{{-- Public Sans: the open-source typeface built for government
    interfaces. Falls back to the system UI stack if Google Fonts is
    unreachable, so a blocked font never blocks the page. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    {{-- Design tokens -- the written source of truth for every value here
        (and the contrast ratio behind each pairing) is
        docs/design-system.md. Change it there first. --}}
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['"Public Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', '"Segoe UI"', 'Roboto', 'Arial', 'sans-serif'],
                },
                colors: {
                    // DEFAULT/dark/light kept as-is: pages already use
                    // bg-brand, hover:bg-brand-dark, bg-brand-light.
                    brand: {
                        DEFAULT: '#2563EB',
                        dark: '#1D4ED8',
                        light: '#DBEAFE',
                        50: '#EFF6FF',
                        100: '#DBEAFE',
                        200: '#BFDBFE',
                        600: '#2563EB',
                        700: '#1D4ED8',
                        800: '#1E40AF',
                    },
                    // The logo's own navy (public/images/elikas-logo-mark.png).
                    navy: { DEFAULT: '#094776', dark: '#073A61' },
                    // Form-field border: 3.30:1 on white, the WCAG 1.4.11
                    // minimum for a control's boundary (gray-300 was 1.47).
                    field: '#868E9C',
                },
            },
        },
    };
</script>
<style>
    .hidden { display: none; }
</style>
{{-- Component classes (see docs/design-system.md section 4). In
    @layer components, so a utility on the same element always wins --
    e.g. "btn btn-primary w-full". None of them set display where a page
    toggles hidden/flex itself (modals, callouts, empty states). --}}
<style type="text/tailwindcss">
    @layer base {
        body { @apply font-sans text-gray-900 bg-gray-50 antialiased; }
        ::placeholder { color: #6B7280; opacity: 1; }
        input[type="checkbox"], input[type="radio"] { accent-color: #2563EB; }
        :focus-visible { outline: 2px solid #1D4ED8; outline-offset: 2px; }
        #sidebar :focus-visible { outline-color: #FFFFFF; }
        /* Leaflet sets its own Helvetica stack; keep map popups/controls in
           the app's typeface. (body prefix outranks leaflet.css, which
           loads later.) */
        body .leaflet-container { font-family: inherit; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
        }
    }

    @layer components {
        /* Page header */
        .page-header { @apply flex flex-wrap items-start justify-between gap-4 mb-6; }
        .page-title { @apply text-[1.375rem] leading-7 font-semibold tracking-tight text-gray-900; }
        .page-subtitle { @apply mt-1 text-sm text-gray-600 max-w-2xl; }

        /* Cards */
        .card { @apply bg-white border border-gray-200 rounded-xl; }
        .card-header { @apply flex items-center justify-between gap-3 mb-3; }
        .card-title { @apply text-sm font-semibold text-gray-900; }
        .icon-chip { @apply w-9 h-9 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center shrink-0; }

        /* Stat strip: one ruled card of figures. Each cell draws its own
           right/bottom hairline; the container's overflow clips the ones
           on the outer edge, so it wraps cleanly at any column count. */
        .stat-strip { @apply grid bg-white border border-gray-200 rounded-xl overflow-hidden; }
        .stat { @apply relative min-w-0 px-4 py-3.5; box-shadow: 1px 0 0 0 #E5E7EB, 0 1px 0 0 #E5E7EB; }
        .stat-label { @apply flex items-center gap-1.5 text-xs font-medium text-gray-600; }
        .stat-value { @apply mt-1 text-2xl leading-8 font-semibold text-gray-900; }
        .stat-note { @apply mt-0.5 text-xs text-gray-500; }
        .stat-alert .stat-value { @apply text-red-700; }
        .stat-alert .stat-label::before { content: ''; @apply w-2 h-2 rounded-full bg-red-600 shrink-0; }

        /* Buttons */
        .btn { @apply inline-flex items-center justify-center gap-1.5 rounded-lg border border-transparent px-4 py-2 text-sm font-medium leading-5 whitespace-nowrap transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
        .btn-sm { @apply px-3 py-1.5 text-xs leading-4; }
        .btn-primary { @apply bg-brand text-white hover:bg-brand-dark; }
        .btn-secondary { @apply bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900; }
        .btn-neutral { @apply bg-gray-800 text-white hover:bg-gray-900; }
        .btn-danger { @apply bg-red-600 text-white hover:bg-red-700; }
        .btn-danger-secondary { @apply bg-white text-red-700 border-red-200 hover:bg-red-50 hover:border-red-300; }
        .btn-attention { @apply bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100; }
        .btn-ghost { @apply text-gray-600 hover:bg-gray-100 hover:text-gray-900; }
        .btn-icon { @apply inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors; }
        .link { @apply font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2; }
        .link-danger { @apply font-medium text-red-700 hover:underline underline-offset-2; }

        /* Filter chips */
        .chip { @apply inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors; }
        .chip-active { @apply border-brand bg-brand-50 text-brand-700 font-medium hover:bg-brand-50; }

        /* Forms */
        .label { @apply block mb-1 text-sm font-medium text-gray-700; }
        .label-sm { @apply block mb-1 text-xs font-medium text-gray-600; }
        .help { @apply mt-1 text-xs text-gray-500; }
        .input { @apply w-full rounded-lg border border-field bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/25 disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-gray-100 disabled:text-gray-600; }
        .input-sm { @apply px-2.5 py-1.5; }
        /* Inline field errors (showFormErrors in public/js/api.js):
           red-600 border is 4.83:1, red-700 message text 6.47:1 on white. */
        .input[aria-invalid="true"] { @apply border-red-600 focus:border-red-600 focus:ring-red-600/25; }
        .field-error { @apply mt-1 flex items-start gap-1 text-xs font-medium text-red-700; }

        /* Toasts (Ui.toast in public/js/ui.js): white on gray-900, 17.74:1.
           An overlay, so it gets a shadow. */
        .ui-toast { @apply pointer-events-auto flex items-start gap-2.5 w-full sm:w-auto sm:min-w-[16rem] sm:max-w-sm rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white shadow-lg; animation: ui-toast-in 160ms ease-out; }
        .ui-toast-leaving { opacity: 0; transform: translateY(4px); transition: opacity 200ms, transform 200ms; }
        .ui-toast-close { @apply -my-0.5 -mr-1 inline-flex items-center justify-center w-6 h-6 shrink-0 rounded text-gray-300 hover:bg-white/10 hover:text-white; }
        @keyframes ui-toast-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

        /* Badges */
        .badge { @apply inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium leading-4 whitespace-nowrap ring-1 ring-inset; }
        .badge-neutral { @apply bg-gray-100 text-gray-700 ring-gray-500/20; }
        .badge-success { @apply bg-green-50 text-green-800 ring-green-600/20; }
        .badge-warning { @apply bg-amber-50 text-amber-800 ring-amber-600/30; }
        .badge-danger { @apply bg-red-50 text-red-700 ring-red-600/20; }
        .badge-info { @apply bg-blue-50 text-blue-700 ring-blue-600/20; }
        .badge-advisory { @apply bg-orange-50 text-orange-800 ring-orange-600/25; }
        .badge-teal { @apply bg-teal-50 text-teal-800 ring-teal-600/20; }

        /* Callouts (block, not flex: form-error boxes hold several <p>s) */
        .callout { @apply rounded-lg border px-3 py-2.5 text-sm; }
        .callout-danger { @apply bg-red-50 border-red-200 text-red-800; }
        .callout-warning { @apply bg-amber-50 border-amber-200 text-amber-900; }
        .callout-info { @apply bg-blue-50 border-blue-200 text-blue-900; }
        .callout-success { @apply bg-green-50 border-green-200 text-green-900; }

        /* Tables */
        /* relative: contains the sr-only header labels, so they stay inside
           the table's own overflow-x-auto wrapper on narrow screens. */
        .data-table { @apply relative w-full text-sm; }
        .data-table thead th { @apply bg-gray-50 px-4 py-2.5 text-left text-xs font-semibold text-gray-600 border-b border-gray-200 whitespace-nowrap; }
        .data-table tbody td { @apply px-4 py-3 text-gray-700 border-b border-gray-100 align-middle; }
        .data-table tbody tr:last-child td { @apply border-b-0; }
        .data-table .num { @apply text-right tabular-nums; }
        .data-table tr.row-link { @apply cursor-pointer hover:bg-gray-50; }
        .data-table tfoot td, .data-table tr.row-total td { @apply px-4 py-3 border-t-2 border-gray-300 font-bold text-gray-900; }
        .table-meta { @apply px-4 py-3 border-t border-gray-200 text-xs text-gray-500; }

        /* Meters */
        .meter { @apply w-full h-1.5 rounded-full bg-gray-100 overflow-hidden; }
        .meter-fill { @apply h-full rounded-full; }

        /* Modals (backdrop display is toggled by each page: hidden/flex) */
        .modal-backdrop { @apply fixed inset-0 z-50 items-center justify-center bg-gray-900/50 p-4; }
        .modal { @apply w-full max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-xl; }
        .modal-header { @apply flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4; }
        .modal-title { @apply text-base font-semibold text-gray-900; }
        .modal-footer { @apply flex flex-wrap justify-end gap-2 border-t border-gray-200 pt-4; }

        /* Sidebar navigation */
        .nav-link { @apply relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium leading-5 text-[#C7D7F0] transition-colors hover:bg-white/10 hover:text-white; }
        .nav-link i { font-size: 18px; width: 18px; text-align: center; }
        .nav-link.active { @apply bg-white/15 text-white font-semibold; }
        .nav-link.active::before { content: ''; @apply absolute left-0 top-2 bottom-2 w-[3px] rounded-r bg-blue-300; }
        .nav-group-label { @apply px-3 mb-1 text-xs font-semibold text-[#A8C2E8]; }
    }
</style>
