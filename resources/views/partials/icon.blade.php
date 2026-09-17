<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($name)
        @case('Inicio')<path d="m3 10 9-7 9 7v10H3Z M9 20v-7h6v7"/>@break
        @case('Fincas')<path d="m3 10 9-7 9 7v11H3Z M8 21v-9h8v9 M8 12l8 9m0-9-8 9"/>@break
        @case('Lotes')<path d="m3 7 9-4 9 4-9 4Z M3 12l9 4 9-4 M3 17l9 4 9-4"/>@break
        @case('Ganado')<path d="M7 8 4 4 3 9l4 3m10-4 3-4 1 5-4 3 M7 8h10l-1 10-4 3-4-3Z M9 16h6 M10 11h.01M14 11h.01"/>@break
        @case('Veterinarios')<path d="M5 3v6a5 5 0 0 0 10 0V3 M3 3h4m6 0h4 M10 14v2a5 5 0 0 0 10 0v-3"/><circle cx="20" cy="10" r="2"/>@break
        @case('Catálogo de vacunas')@case('Vacunaciones')<path d="m15 3 6 6m-4-4-4 4m-4-2 8 8 M10 8l6 6-8 8-6-6Z M3 21l-2 2 M7 12l3 3"/>@break
        @case('Ordeños')<path d="M9 3h6v4l3 5v9H6v-9l3-5Z M9 7h6 M6 13h12"/><path d="M12 15v3"/>@break
        @case('Producción y reportes')<path d="M4 3v18h17 M8 17v-6m5 6V7m5 10V4"/>@break
        @default<path d="m12 3 10 18H2Z M12 9v5m0 3h.01"/>
    @endswitch
</svg>
