# FIX URGENTE (NO ROMPER NADA): Filament 3 cargando un CSS inexistente

Contexto:
- Proyecto Laravel + Filament v3 (panel /admin).
- En el navegador (DevTools Console) aparece:
  GET http://127.0.0.1:8000/css/filament/admin/theme.css 404 (Not Found)
- Yo YA NO QUIERO usar ese theme.css ni “make:filament-theme”.
- Quiero que vuelva a verse normal como antes (sin estilos gigantes raros / blanco).
- NO ALTERAR funcionalidades: recursos, widgets, roles, permisos, rutas, lógica de negocio, etc.
- Solo arreglar el tema visual roto y el 404.

Objetivo:
1) Encontrar EXACTAMENTE dónde se está generando o incluyendo el link a:
   /css/filament/admin/theme.css
2) Eliminar esa referencia (o desactivarla) SIN tocar el resto del sistema.
3) Limpiar caches correctos y asegurar que Filament cargue sus assets estándar.
4) Dejar el panel con estilos normales (Filament default) y sin errores 404 en consola.

Restricciones:
- No modificar modelos, migraciones, controllers, policies, permissions.
- No tocar páginas / recursos salvo que ahí esté la inclusión del CSS.
- No introducir Tailwind 4 ni plugins de Vite; mantener lo estable.
- Cambios mínimos: solo lo necesario para quitar la referencia del theme y restaurar estilos.

Tareas que debes hacer (Copilot):
A) Buscar en TODO el repo referencias a:
   - "css/filament/admin/theme.css"
   - "filament/admin/theme.css"
   - "theme.css"
   - "viteTheme("
   - "->theme(" o "->viteTheme("
   y listar archivos exactos donde aparezcan.
B) Identificar la fuente real del 404:
   - Puede venir de un blade publicado en resources/views/vendor/filament
   - Puede venir de un layout o vista custom (resources/views/filament/**)
   - Puede venir de un widget/page custom.
C) Aplicar el fix:
   - Quitar el <link> / referencia a theme.css si existe en blades.
   - Si existe en PanelProvider, removerlo (actualmente NO debería estar).
   - Si no se encuentra, revisar vistas publicadas y caches.
D) Ejecutar/indicar comandos para limpiar:
   php artisan optimize:clear
   php artisan filament:assets
E) Validación:
   - Abrir /admin y confirmar que no hay 404 a theme.css
   - Confirmar que el CSS y layout de Filament lucen normales (no tipografías enormes/rotas)

Entrega:
- Reporte breve: “Encontré la referencia en X archivo línea Y. La removí.”
- Commit sugerido: "fix: remove missing filament admin theme.css reference"