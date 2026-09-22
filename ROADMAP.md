# Roadmap: Clevers Product Carousel

## Objetivo
Mejorar producto, DX y robustez del plugin en 8 frentes priorizados.

## Estado (Feb 25, 2026)

1. Admin UX
- [x] Completar campos `orderby/order`
- [x] Selector real de categorías `product_cat`
- [x] Metabox con shortcode copiable
- [x] Preview básico del preset

2. Lógica de filtros
- [x] Evitar que `on_sale` y `on_featured` se pisen
- [x] Definir estrategia de combinación (intersección por defecto + filtro)

3. Caché / invalidación
- [x] Extender hooks de invalidación (producto, variación, ventas programadas, stock/precio)
- [x] Mantener versión global de caché

4. A11y + UX frontend
- [x] Pausa autoplay en hover/focus
- [x] Mejoras de labels/ARIA para navegación Slick
- [x] Soporte `prefers-reduced-motion`
- [x] Mejorar comportamiento con foco teclado

5. Bloque Gutenberg
- [x] Bloque dinámico con selector de carrusel
- [x] Render server-side reutilizando shortcode/render actual

6. Compatibilidad / extensibilidad
- [x] Más `orderby` soportados
- [x] Nuevos filtros/hooks en query y render
- [x] Mejoras de override de templates

7. Hardening / calidad
- [x] Sanitización de colores con `sanitize_hex_color`
- [x] Clamps/rangos en inputs numéricos y presets
- [x] Evitar instanciación innecesaria de render en cards

8. Documentación
- [x] Alinear `README.md` con nombre real del plugin
- [x] Corregir shortcode y rutas
- [x] Actualizar estructura y ejemplos

## Notas
- Mantener compatibilidad con configuraciones existentes (`_clevprca_settings`).
- Priorizar cambios incrementales y seguros sobre refactors grandes.
