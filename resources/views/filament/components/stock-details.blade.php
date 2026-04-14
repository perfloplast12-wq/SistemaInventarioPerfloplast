<div class="p-4">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b dark:border-gray-700">
                <th class="py-2 px-1">Ubicación</th>
                <th class="py-2 px-1 text-center">Color</th>
                <th class="py-2 px-1 text-right">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @php
                $stocks = $record->stocks()->with(['warehouse', 'truck', 'color'])->get();
            @endphp
            @forelse($stocks as $stock)
                <tr class="border-b dark:border-gray-800 last:border-0">
                    <td class="py-2 px-1">
                        <div class="flex items-center gap-2">
                            @if($stock->warehouse_id)
                                <x-heroicon-o-building-library class="h-4 w-4 text-gray-400" />
                                <span>{{ $stock->warehouse->name }}</span>
                            @elseif($stock->truck_id)
                                <x-heroicon-o-truck class="h-4 w-4 text-gray-400" />
                                <span>{{ $stock->truck->name }} ({{ $stock->truck->plate }})</span>
                            @else
                                <span class="text-gray-400 italic">Desconocido</span>
                            @endif
                        </div>
                    </td>
                    <td class="py-2 px-1 text-center">
                        @if($stock->color_id)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                {{ $stock->color->code }}
                            </span>
                        @else
                            <span class="text-gray-400 italic">-</span>
                        @endif
                    </td>
                    <td class="py-2 px-1 text-right font-mono font-bold">
                        {{ number_format($stock->quantity, 2) }} {{ $record->unitOfMeasure?->name }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="py-4 text-center text-gray-500 italic">
                        Sin existencias registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="font-bold border-t-2 dark:border-gray-600">
                <td colspan="2" class="py-2 px-1 text-primary-600">TOTAL</td>
                <td class="py-2 px-1 text-right text-primary-600 font-mono">
                    {{ number_format($stocks->sum('quantity'), 2) }} {{ $record->unitOfMeasure?->name }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
