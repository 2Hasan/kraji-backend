{{-- ترقيم بسيط بأزرار عربية. قالب Laravel الافتراضي يعتمد على Tailwind وما يتعرض صح هنا. --}}
@if ($paginator->hasPages())
  <nav class="pager-nav" aria-label="تنقل بين الصفحات">
    <span class="muted">صفحة {{ $paginator->currentPage() }} من {{ $paginator->lastPage() }}</span>
    <div class="actions">
      @if ($paginator->onFirstPage())
        <span class="btn btn-ghost btn-sm is-disabled">السابق</span>
      @else
        <a class="btn btn-ghost btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
      @endif

      @if ($paginator->hasMorePages())
        <a class="btn btn-ghost btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
      @else
        <span class="btn btn-ghost btn-sm is-disabled">التالي</span>
      @endif
    </div>
  </nav>
@endif
