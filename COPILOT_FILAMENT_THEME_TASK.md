# Objetivo (Laravel + Filament)
Necesito unificar el tema visual de Filament para que el modo light y dark se vean consistentes en todo el panel.

## Problemas actuales
1) Inventario y Catálogos son Pages con Blade custom y usan bg-white hardcode, por eso en light se ven deslavados y distintos.
2) Users / Bitácora (Resources nativos) usan estilos Filament y sí cambian, pero algunos controles como toggles (Activo) no se distinguen en light.
3) Quiero un tema "fábrica" (PERFLO-PLAST) con fondo claro suave (no blanco puro) y dark elegante.
4) Quiero que el sidebar tenga fondo propio y que las tarjetas/secciones se vean como "paneles".

## Tareas que debes hacer
### Etapa 1 — Diagnóstico
- Revisar AdminPanelProvider.php y detectar si el panel está aplicando un theme CSS (viteTheme) o no.
- Revisar si el build de Vite está correcto y si theme.css está siendo cargado.

### Etapa 2 — Tema global
- Crear/ajustar el archivo: resources/css/filament/admin/theme.css
- Usar tokens CSS para fondo/surface/border.
- Estilizar globalmente:
  - background del panel
  - sidebar
  - topbar
  - cards/sections/widgets/tables
  - inputs/search fields
  - toggles en tablas y forms (Activo)

### Etapa 3 — Refactor Pages Blade (Inventario y Catálogos)
- Reemplazar bg-white hardcode por clases compatibles con light/dark.
- Mantener el layout y proporciones actuales.

### Etapa 4 — Confirmación
- Proponer checklist visual para validar en:
  - /admin/users
  - /admin/audit-logs (bitácora)
  - /admin/inventario
  - /admin/catalogos
- Asegurar que light/dark se aplique igual.

## Restricciones
- No usar plugins extra raros.
- No romper Filament defaults: solo overrides CSS + refactor de blade.
- Mantener accesibilidad (contraste aceptable).
