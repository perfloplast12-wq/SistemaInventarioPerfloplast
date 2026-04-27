<x-filament-panels::page>
    <div style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
        <h2 style="font-size: 20px; font-weight: 700; color: #6366f1;">Modo de Emergencia Activo</h2>
        <p style="margin-top: 10px; color: #64748b;">Estamos diagnosticando el error 500. Si ves esta página, el sistema base funciona correctamente.</p>
        
        <div style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div style="padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <strong>Productos Totales:</strong> {{ number_format($this->totalProducts) }}
            </div>
            <div style="padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <strong>Materias Primas:</strong> {{ number_format($this->rawMaterials) }}
            </div>
        </div>
        <p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">Siguiente paso: Reactivar las secciones de inventario gradualmente.</p>
    </div>
</x-filament-panels::page>
