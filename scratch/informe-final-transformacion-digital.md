# Informe Final: Propuesta Integral de Transformación Digital

## 1. Portada

**Título del proyecto:** Propuesta integral de transformación digital para Perflo Plast  
**Empresa:** Perflo Plast  
**Tipo de empresa:** Empresa industrial y comercial dedicada a la producción, inventario, venta y distribución de productos plásticos.  
**Sistema analizado:** Plataforma web administrativa Perflo Plast  
**Tecnologías principales:** Laravel, Filament, PHP, MySQL, Redis, Laravel Sanctum, Laravel Reverb, ApexCharts, Leaflet, Tailwind CSS, Vite y Docker.  
**Curso:** Transformación Digital  
**Estudiante:** [Nombre del estudiante]  
**Docente:** [Nombre del docente]  
**Fecha:** [Fecha de entrega]

## 2. Resumen ejecutivo

El presente informe describe una propuesta integral de transformación digital para Perflo Plast, tomando como base el sistema web desarrollado para administrar procesos clave de la empresa: inventario, producción, ventas, pedidos, despachos, facturación, seguimiento GPS, reportes gerenciales y auditoría de operaciones.

La empresa requiere controlar con mayor precisión sus existencias, mejorar la trazabilidad de los productos, reducir errores manuales, agilizar las ventas, coordinar entregas y disponer de información confiable para la toma de decisiones. El sistema analizado responde a estas necesidades mediante una plataforma centralizada construida con Laravel y Filament, que integra módulos operativos y administrativos en un mismo entorno.

La solución permite registrar productos terminados y materia prima, gestionar bodegas y camiones, controlar movimientos de inventario, confirmar ventas con validación estricta de stock, generar pedidos, asignar despachos, registrar entregas, emitir facturas o recibos, monitorear rutas mediante geolocalización y visualizar indicadores por medio de dashboards. Además, incorpora roles y permisos, bitácora de auditoría, usuarios activos/inactivos y generación de reportes exportables.

Como resultado, la propuesta busca fortalecer la eficiencia operativa, la trazabilidad, la seguridad de la información y la capacidad de análisis gerencial. Su implementación gradual permite reducir riesgos, capacitar a los usuarios por área y medir el impacto a través de indicadores como exactitud de inventario, tiempo de despacho, ventas confirmadas, cumplimiento de entregas, reducción de errores y uso de reportes.

## 3. Descripción de la empresa

Perflo Plast es una empresa enfocada en la producción y comercialización de productos plásticos. Sus operaciones incluyen procesos de fabricación, manejo de materia prima, control de producto terminado, almacenamiento en bodegas, ventas a clientes, asignación de pedidos, distribución mediante camiones, facturación y seguimiento de entregas.

La operación diaria requiere coordinación entre varias áreas: producción, bodega, ventas, logística, contabilidad, administración y conductores. Cada área genera información que afecta directamente a las demás. Por ejemplo, una venta confirmada debe validar disponibilidad de inventario; un despacho debe cargar producto desde bodega hacia un camión; una entrega debe actualizar el estado del pedido; y una devolución debe impactar nuevamente el inventario.

Antes de una transformación digital completa, estos procesos suelen depender de registros manuales, hojas de cálculo, comunicación informal o validaciones tardías. Esto puede ocasionar diferencias de inventario, duplicidad de datos, falta de visibilidad sobre rutas, retrasos en entregas y dificultad para obtener indicadores confiables.

La propuesta digital consiste en consolidar estos procesos en una plataforma web administrativa que permita controlar las operaciones en tiempo real, registrar cada acción importante y generar información gerencial útil para la toma de decisiones.

## 4. Diagnóstico actual de procesos y uso de tecnología

El análisis del sistema evidencia que Perflo Plast cuenta con una plataforma web basada en Laravel 12 y Filament 3.3. El sistema está orientado a cubrir procesos operativos completos, no únicamente registros básicos. La arquitectura utiliza modelos, recursos administrativos, servicios de negocio, observadores, migraciones, dashboards, exportaciones y rutas web/API.

### Procesos actuales identificados

**Gestión de usuarios y seguridad:** El sistema permite administrar usuarios, activar o desactivar cuentas, asignar roles y controlar permisos granulares. Existen roles como super administrador, administrador, bodega, ventas, contabilidad, producción, conductor, visualizador y mantenimiento.

**Catálogos maestros:** Se administran productos, colores, unidades de medida, bodegas, camiones, turnos y ubicaciones. Estos catálogos sirven como base para los procesos de inventario, producción, ventas y logística.

**Inventario:** El sistema controla existencias por producto, color, bodega y camión. Los movimientos de inventario incluyen entradas, salidas, transferencias, ajustes y devoluciones. La lógica de stock utiliza transacciones de base de datos y bloqueo de registros para evitar inconsistencias cuando varios usuarios operan al mismo tiempo.

**Producción:** Se registran producciones, turnos, productos consumidos y productos generados. Esto permite conectar la operación de fábrica con el inventario disponible.

**Ventas:** Las ventas se registran con productos, cantidades, precios, descuentos, pagos y estado. Antes de confirmar una venta, el sistema valida cliente, origen del stock y disponibilidad de inventario. Al confirmar, se actualiza el estado y se envía trabajo a cola para procesos posteriores.

**Pedidos y despachos:** Los pedidos se asignan a despachos, camiones y conductores. El despacho puede pasar por estados como pendiente, en progreso, completado y entregado. Al iniciar un despacho se transfiere o carga stock al camión; al entregar, se descuenta el stock correspondiente y se puede generar facturación.

**Facturación y reportes:** El sistema incluye facturas/recibos, generación de PDF, exportaciones a Excel y reportes gerenciales. Esto fortalece el control contable y la revisión administrativa.

**Seguimiento GPS:** Existen rutas y componentes para registrar ubicaciones de vendedores o conductores, mostrar mapas y consultar la última ubicación de un despacho. Se utiliza Leaflet para visualización geográfica.

**Auditoría:** El sistema registra eventos como creación, actualización y eliminación de registros. La bitácora guarda usuario, módulo, cambios, IP, navegador, URL y método HTTP, evitando almacenar datos sensibles como contraseñas.

### Herramientas tecnológicas detectadas

La plataforma utiliza PHP 8.2, Laravel 12, Filament 3.3, MySQL o base de datos relacional compatible, Redis/Predis para colas y caché, Laravel Reverb/Pusher para eventos en tiempo real, Laravel Sanctum para tokens, Spatie Permission para roles y permisos, DomPDF para PDF, Maatwebsite Excel para exportaciones, ApexCharts para gráficos, Leaflet para mapas, Tailwind CSS y Vite para interfaz, y Docker para despliegue.

### Principales hallazgos del diagnóstico

La empresa ya posee una base tecnológica sólida para digitalizar sus operaciones principales. La mayor fortaleza del sistema es que conecta procesos que normalmente se manejan por separado: ventas, inventario, producción, logística y facturación. También existe enfoque en seguridad por roles, auditoría y validaciones de stock.

Como oportunidades de mejora se identifican la necesidad de documentar mejor los procesos, fortalecer las pruebas automatizadas, eliminar o proteger rutas de diagnóstico/mantenimiento, estandarizar manuales de usuario, mejorar la cobertura de respaldo y recuperación, y formalizar indicadores de desempeño para medir el impacto de la digitalización.

## 5. Análisis FODA

### Fortalezas

- Plataforma web centralizada para inventario, producción, ventas, logística, facturación y reportes.
- Validación estricta de stock antes de confirmar ventas o movimientos.
- Uso de transacciones de base de datos para reducir inconsistencias.
- Control de usuarios mediante roles y permisos granulares.
- Bitácora de auditoría para trazabilidad de cambios.
- Dashboards gerenciales con indicadores de ventas, inventario, producción y logística.
- Seguimiento GPS de vendedores o despachos mediante mapas.
- Capacidad de generar PDF y exportaciones a Excel.
- Arquitectura moderna basada en Laravel, Filament, colas y componentes reutilizables.

### Oportunidades

- Reducir dependencia de registros manuales y hojas de cálculo.
- Mejorar la toma de decisiones con indicadores en tiempo real.
- Integrar notificaciones automáticas para stock bajo, pedidos atrasados o rutas fuera de tiempo.
- Implementar analítica predictiva para demanda, compras y producción.
- Conectar el sistema con facturación electrónica, contabilidad externa o sistemas de proveedores.
- Desarrollar una aplicación móvil o interfaz optimizada para conductores y vendedores.
- Fortalecer pruebas automatizadas y documentación técnica.

### Debilidades

- La adopción del sistema depende de la capacitación de los usuarios.
- Algunos procesos críticos pueden requerir conexión estable a internet.
- Existen rutas de diagnóstico y mantenimiento que deben revisarse para ambientes productivos.
- La documentación funcional y manuales de usuario parecen limitados.
- La cobertura de pruebas automatizadas todavía puede ampliarse.
- El sistema requiere administración técnica para despliegue, respaldos, actualizaciones y monitoreo.

### Amenazas

- Riesgo de accesos no autorizados si no se aplican buenas prácticas de contraseñas y permisos.
- Pérdida o corrupción de datos si no existen respaldos periódicos.
- Resistencia al cambio por parte de usuarios acostumbrados a procesos manuales.
- Errores operativos durante la migración inicial de inventario.
- Dependencia de servidores, red, energía eléctrica y servicios externos.
- Riesgos de privacidad asociados al manejo de ubicaciones GPS.

## 6. Propuesta de transformación digital

La propuesta consiste en implementar y consolidar la plataforma web Perflo Plast como sistema central de operación empresarial. El objetivo es que las áreas de producción, bodega, ventas, logística, contabilidad y gerencia trabajen sobre una misma fuente de datos, eliminando duplicidad de registros y aumentando la trazabilidad de cada proceso.

### Objetivos estratégicos

1. Centralizar la información operativa de la empresa en una plataforma web segura.
2. Aumentar la exactitud del inventario mediante movimientos controlados y trazables.
3. Reducir errores en ventas y despachos validando stock antes de confirmar operaciones.
4. Mejorar la coordinación entre ventas, bodega, producción y logística.
5. Agilizar la generación de facturas, recibos, reportes PDF y exportaciones.
6. Dar visibilidad gerencial mediante dashboards e indicadores actualizados.
7. Incorporar seguimiento GPS para mejorar el control de rutas, vendedores y entregas.
8. Proteger la información mediante roles, permisos, auditoría y control de usuarios activos.
9. Crear una base tecnológica escalable para futuras integraciones y automatizaciones.

### Herramientas a utilizar

**Laravel:** Framework principal para desarrollar la lógica del sistema, rutas, servicios, modelos, migraciones y seguridad.

**Filament:** Panel administrativo para crear interfaces de gestión, formularios, tablas, páginas, widgets y recursos internos.

**MySQL o base relacional:** Almacenamiento estructurado de usuarios, productos, inventario, ventas, pedidos, despachos, facturas y auditoría.

**Redis y colas:** Procesamiento en segundo plano para tareas que no deben bloquear al usuario, como confirmación de ventas o procesos posteriores.

**Spatie Laravel Permission:** Administración de roles y permisos por área de trabajo.

**Laravel Sanctum:** Manejo de tokens y autenticación para posibles integraciones API.

**Laravel Reverb/Pusher:** Comunicación en tiempo real para eventos, notificaciones y actualizaciones.

**ApexCharts:** Visualización de indicadores mediante gráficos gerenciales.

**Leaflet:** Visualización de mapas y ubicaciones GPS.

**DomPDF:** Generación de documentos PDF como facturas y reportes.

**Maatwebsite Excel:** Exportación de información operativa y gerencial a hojas de cálculo.

**Tailwind CSS y Vite:** Interfaz moderna, adaptable y eficiente.

**Docker:** Despliegue más controlado y replicable entre ambientes.

### Justificación de la elección tecnológica

Laravel y Filament son adecuados para esta propuesta porque permiten construir aplicaciones administrativas robustas en menor tiempo, con seguridad, formularios, tablas, validaciones, relaciones y autenticación integradas. Esta combinación se adapta bien a empresas que necesitan digitalizar procesos internos sin desarrollar cada pantalla desde cero.

El uso de una base de datos relacional permite mantener consistencia entre productos, inventario, ventas, pedidos y facturas. Las transacciones y bloqueos utilizados en la lógica de stock son importantes porque evitan que dos usuarios descuenten la misma existencia al mismo tiempo.

Redis y las colas permiten que tareas pesadas se procesen en segundo plano. Esto mejora la experiencia del usuario y prepara el sistema para crecer. Las herramientas de reportes, PDF y Excel facilitan que la empresa siga utilizando formatos conocidos, pero generados automáticamente desde datos confiables.

La incorporación de roles, permisos y bitácora responde a necesidades de control interno. Cada usuario accede únicamente a los módulos que necesita, y las acciones importantes quedan registradas para auditoría.

Finalmente, Leaflet y el seguimiento GPS aportan valor directo al área logística porque permiten conocer ubicaciones recientes, controlar rutas y mejorar la coordinación de entregas.

## 7. Plan de implementación

La implementación debe realizarse por fases para reducir riesgos y permitir adaptación gradual de los usuarios.

### Cronograma sugerido

| Fase | Duración estimada | Actividades principales | Resultado esperado |
|---|---:|---|---|
| 1. Preparación | 1 semana | Revisar procesos actuales, limpiar datos, definir responsables y validar requerimientos por área. | Alcance confirmado y datos base listos. |
| 2. Configuración inicial | 1 semana | Crear usuarios, roles, permisos, bodegas, camiones, unidades, colores, turnos y productos. | Catálogos maestros configurados. |
| 3. Inventario y producción | 2 semanas | Cargar existencias iniciales, registrar movimientos, validar producción y capacitar a bodega/fábrica. | Inventario operativo y trazable. |
| 4. Ventas y pedidos | 2 semanas | Capacitar vendedores, registrar ventas, confirmar stock, generar pedidos y validar pagos. | Ventas integradas al inventario. |
| 5. Logística y GPS | 2 semanas | Asignar despachos, conductores, rutas, seguimiento GPS y control de entregas/devoluciones. | Despachos controlados digitalmente. |
| 6. Facturación y reportes | 1 semana | Generar facturas, PDF, Excel, dashboards e indicadores gerenciales. | Información administrativa disponible. |
| 7. Evaluación y ajustes | 1 semana | Revisar errores, medir indicadores, ajustar permisos, mejorar pantallas y documentar procesos. | Sistema estabilizado y listo para operación continua. |

### Roles y responsables

**Gerencia:** Define prioridades, valida indicadores y aprueba políticas de uso del sistema.

**Administrador del sistema:** Crea usuarios, gestiona permisos, revisa bitácoras, configura parámetros y coordina soporte.

**Bodega:** Registra entradas, salidas, transferencias, ajustes y valida existencias físicas.

**Producción:** Registra producciones, turnos, consumo de materia prima y generación de producto terminado.

**Ventas:** Registra clientes, ventas, descuentos, pagos y pedidos.

**Logística:** Organiza despachos, asigna camiones y conductores, da seguimiento a entregas y devoluciones.

**Conductores:** Consultan despachos asignados, reportan avance de entrega y permiten seguimiento GPS cuando corresponda.

**Contabilidad:** Revisa ventas, facturas, pagos, reportes y exportaciones.

**Soporte técnico:** Administra despliegue, actualizaciones, respaldos, monitoreo, seguridad y solución de incidentes.

### Recursos y costos estimados

Los costos pueden variar según infraestructura, cantidad de usuarios y alcance final. Para fines académicos se propone una estimación por categorías:

| Recurso | Descripción | Costo estimado |
|---|---|---:|
| Servidor o hosting VPS | Ambiente para alojar aplicación, base de datos y servicios. | Q 300 a Q 900 mensuales |
| Dominio y certificado SSL | Acceso seguro al sistema. | Q 100 a Q 250 anuales |
| Implementación técnica | Configuración, despliegue, migración y pruebas. | Q 5,000 a Q 15,000 |
| Capacitación | Sesiones por área y material de apoyo. | Q 2,000 a Q 5,000 |
| Mantenimiento mensual | Soporte, respaldos, monitoreo y mejoras menores. | Q 1,500 a Q 4,000 mensuales |
| Equipos móviles | Teléfonos o tablets para vendedores/conductores, si aplica. | Según cantidad de usuarios |
| Internet/datos móviles | Conectividad para GPS y trabajo fuera de oficina. | Según plan contratado |

Para reducir costos iniciales, la empresa puede iniciar con los módulos más críticos: inventario, ventas y despachos. Luego puede ampliar a analítica avanzada, integraciones contables y mejoras móviles.

## 8. Indicadores de evaluación del éxito

Para medir el impacto de la transformación digital se proponen los siguientes indicadores:

| Indicador | Fórmula o medición | Meta sugerida |
|---|---|---:|
| Exactitud de inventario | Coincidencia entre stock físico y sistema | 95% o más |
| Tiempo de confirmación de venta | Minutos desde registro hasta confirmación | Reducción del 40% |
| Errores por stock insuficiente | Ventas o despachos corregidos por falta de producto | Reducción del 60% |
| Tiempo de preparación de despacho | Horas desde pedido asignado hasta salida | Reducción del 30% |
| Cumplimiento de entregas | Entregas completadas / entregas programadas | 90% o más |
| Uso del sistema | Usuarios activos por día o semana | 80% o más |
| Reportes generados | Reportes PDF/Excel emitidos desde el sistema | Incremento mensual controlado |
| Trazabilidad de cambios | Operaciones críticas con registro en bitácora | 100% |
| Disponibilidad del sistema | Tiempo en línea mensual | 99% |
| Satisfacción de usuarios | Encuesta interna por área | 4/5 o superior |

Estos indicadores deben revisarse semanalmente durante el primer mes y luego de forma mensual. La gerencia puede utilizar el dashboard general para observar tendencias de ventas, producción, inventario, rentabilidad y logística.

## 9. Consideraciones de ciberseguridad y ética

La transformación digital implica mayor responsabilidad sobre la información de la empresa, clientes, empleados y rutas de distribución. Por ello, la propuesta debe incluir controles técnicos, administrativos y éticos.

### Ciberseguridad

- Aplicar autenticación segura para todos los usuarios.
- Usar contraseñas robustas y cambiarlas periódicamente.
- Asignar permisos mínimos según el rol de cada usuario.
- Desactivar cuentas de colaboradores que ya no trabajan en la empresa.
- Mantener el sistema, dependencias y servidor actualizados.
- Usar HTTPS/SSL para proteger la comunicación.
- Realizar respaldos automáticos diarios y pruebas de restauración.
- Revisar periódicamente la bitácora de auditoría.
- Proteger rutas de diagnóstico, migración o mantenimiento para que no estén disponibles públicamente.
- Evitar almacenar contraseñas o tokens en texto plano.
- Separar ambientes de desarrollo, pruebas y producción.
- Monitorear errores, intentos de acceso y actividad inusual.

### Ética y privacidad

- Informar a vendedores y conductores sobre el uso de seguimiento GPS.
- Utilizar la ubicación únicamente para fines laborales, logísticos y de seguridad.
- Limitar el acceso a mapas y ubicaciones solo a personal autorizado.
- No utilizar la información de rutas para fines ajenos a la operación.
- Registrar únicamente los datos necesarios para cumplir el proceso empresarial.
- Garantizar que los reportes no expongan información sensible a usuarios sin autorización.
- Mantener transparencia sobre qué datos se recopilan y por cuánto tiempo se conservan.

## 10. Conclusiones

La plataforma Perflo Plast representa una propuesta sólida de transformación digital porque integra procesos esenciales de la empresa en un solo sistema. El análisis del código muestra módulos funcionales para inventario, producción, ventas, pedidos, despachos, facturación, GPS, reportes, seguridad y auditoría.

Uno de los mayores beneficios esperados es la trazabilidad. Cada venta, movimiento de inventario, despacho o cambio administrativo puede quedar registrado, lo que facilita detectar errores, consultar historial y tomar decisiones con datos confiables.

La digitalización también mejora la coordinación entre áreas. Producción, bodega, ventas y logística dejan de operar como procesos aislados y empiezan a trabajar sobre la misma información. Esto reduce duplicidad, evita ventas sin stock, mejora la preparación de despachos y permite conocer el estado de entregas.

El sistema incorpora herramientas adecuadas para una empresa en crecimiento: roles, permisos, dashboards, exportaciones, PDF, colas, mapas y arquitectura web escalable. Sin embargo, para que la transformación sea exitosa no basta con tener el software; también se requiere capacitación, disciplina operativa, políticas de seguridad, mantenimiento técnico y medición continua de resultados.

Como recomendación final, Perflo Plast debe implementar la solución por fases, iniciar con los procesos críticos, validar datos de inventario, capacitar a cada área y revisar indicadores durante los primeros meses. Con este enfoque, la empresa puede reducir riesgos y obtener beneficios medibles en eficiencia, control y toma de decisiones.

## 11. Anexos

### Anexo 1. Pantallas sugeridas para incluir

- Login del sistema Perflo Plast.
- Dashboard gerencial.
- Módulo de inventario.
- Registro de productos.
- Movimientos de inventario.
- Módulo de producción.
- Registro de ventas.
- Venta rápida de fábrica.
- Módulo de pedidos.
- Módulo de despachos.
- Vista de seguimiento GPS o mapa.
- Factura o recibo PDF.
- Reporte general.
- Bitácora de auditoría.
- Gestión de usuarios y roles.

### Anexo 2. Evidencia técnica del sistema

El sistema contiene módulos y archivos relevantes como:

- Recursos Filament para ventas, pedidos, despachos, inventario, producción, usuarios, facturas, devoluciones y auditoría.
- Servicios de negocio para stock, ventas, facturación, despachos y auditoría.
- Modelos de base de datos para productos, inventario, ventas, pedidos, despachos, ubicaciones, facturas, producción y usuarios.
- Migraciones para crear la estructura de usuarios, permisos, inventario, ventas, producción, GPS, auditoría y reportes.
- Widgets y dashboards para visualizar ventas, inventario, producción, logística y rentabilidad.
- Rutas web y API para administración, PDF, tracking GPS y consultas de ubicación.

### Anexo 3. Flujo general del proceso digital

1. El administrador configura usuarios, roles, productos, bodegas, camiones y turnos.
2. Producción registra productos fabricados y consumo de materia prima.
3. Bodega valida existencias y movimientos de inventario.
4. Ventas registra una venta y el sistema valida stock.
5. La venta confirmada genera pedido y procesos relacionados.
6. Logística asigna pedidos a un despacho y camión.
7. El despacho inicia, carga producto y actualiza estado.
8. El conductor realiza entrega y se registra ubicación GPS.
9. El sistema marca pedidos como completados y genera facturación.
10. Gerencia consulta dashboards, reportes y bitácora para tomar decisiones.

### Anexo 4. Recomendaciones de mejora futura

- Crear manuales de usuario por rol.
- Aumentar pruebas automatizadas para stock, ventas, despachos y facturación.
- Implementar alertas automáticas de stock mínimo.
- Crear reportes de margen por producto, vendedor y ruta.
- Integrar facturación electrónica si aplica.
- Implementar autenticación multifactor para administradores.
- Crear política formal de respaldo y recuperación.
- Optimizar una experiencia móvil para conductores y vendedores.
- Documentar APIs para futuras integraciones.
- Revisar y cerrar rutas de mantenimiento antes de producción.
