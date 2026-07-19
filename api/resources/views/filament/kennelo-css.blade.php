<style>
    @keyframes k-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.45; }
    }

    .k-skeleton { animation: k-pulse 1.8s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

    .k-skel-bar {
        height: 0.75rem;
        border-radius: 0.375rem;
        background-color: #e5e7eb;
    }
    .k-skel-bar.k-lg { height: 1.5rem; }
    .k-skel-bar.k-sm { height: 0.5rem; }
    .dark .k-skel-bar { background-color: rgba(255, 255, 255, 0.1); }

    .k-skel-chart {
        display: flex;
        align-items: flex-end;
        gap: 0.5rem;
        height: 10rem;
    }
    .k-skel-chart-bar {
        flex: 1 1 0%;
        border-radius: 0.25rem 0.25rem 0 0;
        background-color: #e5e7eb;
    }
    .dark .k-skel-chart-bar { background-color: rgba(255, 255, 255, 0.1); }

    .k-skel-circle {
        width: 10rem;
        height: 10rem;
        margin: 1rem auto;
        border-radius: 9999px;
        border: 16px solid #e5e7eb;
    }
    .dark .k-skel-circle { border-color: rgba(255, 255, 255, 0.1); }

    .k-grid { display: grid; gap: 1.5rem; }
    @media (min-width: 768px) { .k-grid-2, .k-grid-3, .k-grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1280px) {
        .k-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .k-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    .k-stack > * + * { margin-top: 0.875rem; }
    .k-stack-sm > * + * { margin-top: 0.5rem; }

    .k-meter-row { display: flex; align-items: center; gap: 0.75rem; }
    .k-meter-label {
        flex-shrink: 0;
        width: 7rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.875rem;
        font-variant-numeric: tabular-nums;
    }
    .k-meter {
        flex: 1 1 0%;
        height: 0.625rem;
        overflow: hidden;
        border-radius: 9999px;
        background-color: #f3f4f6;
    }
    .k-meter.k-lg { height: 0.75rem; }
    .dark .k-meter { background-color: rgba(255, 255, 255, 0.06); }
    .k-meter-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.7s ease;
    }
    .k-fill-primary { background-color: #059669; }
    .k-fill-blue { background-color: #3b82f6; }
    .k-fill-amber { background-color: #f59e0b; }
    .k-fill-green { background-color: #22c55e; }
    .k-meter-value {
        flex-shrink: 0;
        width: 3rem;
        text-align: end;
        font-size: 0.875rem;
        color: #6b7280;
        font-variant-numeric: tabular-nums;
    }
    .dark .k-meter-value { color: #9ca3af; }

    .k-table-wrap { overflow-x: auto; }
    .k-table { width: 100%; font-size: 0.875rem; border-collapse: collapse; }
    .k-table th {
        padding: 0.5rem 0;
        font-weight: 500;
        color: #6b7280;
        text-align: start;
        border-bottom: 1px solid #f3f4f6;
    }
    .k-table th.k-num, .k-table td.k-num { text-align: end; font-variant-numeric: tabular-nums; }
    .k-table td { padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; }
    .k-table tbody tr:last-child td { border-bottom: none; }
    .k-table td.k-strong { font-weight: 500; }
    .dark .k-table th { color: #9ca3af; border-color: rgba(255, 255, 255, 0.1); }
    .dark .k-table td { border-color: rgba(255, 255, 255, 0.1); }

    .k-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.375rem 0;
        font-size: 0.875rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .k-row:last-child { border-bottom: none; }
    .dark .k-row { border-color: rgba(255, 255, 255, 0.1); }
    .k-row-value { font-weight: 500; font-variant-numeric: tabular-nums; }

    .k-funnel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.25rem;
        font-size: 0.875rem;
    }
    .k-funnel-head .k-name { font-weight: 600; }
    .k-funnel-head .k-nums { font-variant-numeric: tabular-nums; }

    .k-muted { color: #6b7280; }
    .dark .k-muted { color: #9ca3af; }
    .k-xs { font-size: 0.75rem; }
    .k-empty { font-size: 0.875rem; color: #6b7280; }
    .dark .k-empty { color: #9ca3af; }

</style>
