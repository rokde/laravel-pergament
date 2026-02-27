{{-- Footer --}}
<footer class="border-t border-gray-200 dark:border-gray-700 pergament-bg print:hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; {{ date('Y') }} {{ config('pergament.site.name', config('app.name', 'Pergament')) }}. All rights reserved.
        </p>
    </div>
</footer>
