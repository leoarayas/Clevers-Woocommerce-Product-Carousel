# 🧩 Clevers Product Carousel

> **Plugin de WordPress + WooCommerce**  
> Crea sliders de productos profesionales, totalmente personalizables y con presets listos para usar.  
> Desarrollado por [Clevers Devs](https://clevers.dev)

![WordPress Tested](https://img.shields.io/badge/Tested%20up%20to-6.7-blue?logo=wordpress)
![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-Compatible-success?logo=woocommerce)
![License](https://img.shields.io/badge/license-GPLv2-orange)
![Version](https://img.shields.io/badge/version-1.4.0-blue)
[![CI](https://github.com/agenciaingenium/Clevers-Woocommerce-Product-Carousel/actions/workflows/php-compatibility.yml/badge.svg)](https://github.com/agenciaingenium/Clevers-Woocommerce-Product-Carousel/actions/workflows/php-compatibility.yml)
[![Release](https://github.com/agenciaingenium/Clevers-Woocommerce-Product-Carousel/actions/workflows/create_release.yml/badge.svg)](https://github.com/agenciaingenium/Clevers-Woocommerce-Product-Carousel/actions/workflows/create_release.yml)

---

## ✨ Características

- 🎨 **4 presets** visuales (personalizables o extendibles).  
- 💅 **Colores configurables** por carrusel (botón, texto, burbuja, borde, fondo, etc.).  
- 🛒 **Filtros dinámicos:** productos destacados, en oferta o por categorías.  
- ⚙️ **Opciones visuales:** autoplay, velocidad, número de slides, flechas, dots.  
- 🚀 **Caché inteligente y variables CSS** por slider.  
- 💾 **Plantillas sobreescribibles** en el tema (estilo WooCommerce).  
- 🧩 **Totalmente responsive** gracias a Slick.js.  

---

## 📦 Instalación

### Desde WordPress
1. Sube el archivo `.zip` desde “Plugins → Añadir nuevo → Subir plugin”.
2. Activa el plugin desde el panel de administración.
3. Crea un nuevo **Product Carousel** desde el menú “Product Carousels”.
4. Inserta el shortcode donde quieras:

```php
[cleverspr_carousel id="123"]
```

---

## ⚙️ Uso

Cada carrusel permite configurar:
- Diseño (*Preset 1-4*)
- Número de slides visibles
- Autoplay, dots y arrows
- Filtros de productos (categorías, destacados, ofertas)
- Colores personalizados (variables CSS)

### 🎨 Ejemplo de personalización vía CSS

```css
#clevers-product-carousel-123 {
  --clevers-primary: #e63946;
  --clevers-secondary: #1d3557;
  --clevers-button-background: #457b9d;
  --clevers-button-text: #ffffff;
}
```

---


## 🛠️ Configuración operativa (soporte técnico)

Esta guía está pensada para operación en sitios con catálogos medianos/grandes y para equipos de soporte.

### 1) Límites por lote (batch)

Aunque el carrusel no usa una cola asíncrona dedicada, el “lote” real de trabajo por render es la cantidad de productos que devuelve la consulta.

- **Recomendado para producción:** 8–24 productos por carrusel.
- **Catálogos grandes o hosting compartido:** empezar con 8–12.
- **Servidor dedicado / buen caché de objetos:** 24–36 si el TTFB se mantiene estable.

Buenas prácticas:
- Evita configurar múltiples carruseles en una misma vista con lotes altos al mismo tiempo.
- Prioriza filtros concretos (categoría, destacados, ofertas) para reducir consultas amplias.
- Ajusta el TTL del caché si necesitas reducir recomputación:

```php
add_filter( 'cleverspr_carousel/cache_ttl', function( $ttl, $carousel_id ) {
	// 15 minutos.
	return 15 * MINUTE_IN_SECONDS;
}, 10, 2 );
```

### 2) Cron recomendado

El plugin invalida caché cuando cambia producto/términos/meta y también en `woocommerce_scheduled_sales`.
Para entornos de alto tráfico, evita depender solo del tráfico web para disparar WP-Cron.

**Recomendación operativa:**
1. Desactivar cron interno en `wp-config.php`:

```php
define( 'DISABLE_WP_CRON', true );
```

2. Configurar cron de sistema cada **5 minutos**:

```bash
*/5 * * * * php /ruta/a/wordpress/wp-cron.php > /dev/null 2>&1
```

Si tu tienda tiene cambios de precio/ofertas muy frecuentes, puedes bajar a cada 1–2 minutos.

### 3) Troubleshooting de cola (métricas de pipeline)

El plugin registra métricas por carrusel (`pending`, `processed`, `failed`, `avg_time_ms_per_product`, `last_error`, `last_run_at`) y las muestra en el panel admin del carrusel.

Checklist rápido de soporte:
- **`failed > 0`**: revisar `last_error` y logs PHP (`debug.log` o logs del servidor).
- **`avg_time_ms_per_product` alto**: reducir lote, aplicar filtros más específicos y revisar rendimiento de WooCommerce/DB.
- **`pending` no baja / render vacío**: validar estado de WooCommerce y plantillas sobrescritas en el tema.
- **Invalidación irregular de datos**: confirmar que WP-Cron está ejecutando correctamente y que no está bloqueado por cachés de página agresivos.

Si necesitas inspección técnica puntual, puedes leer las métricas desde meta del post del carrusel (`_clevprca_queue_metrics`) para diagnóstico remoto.

---

## 🧠 Sobrescribir plantillas

Para personalizar la vista sin tocar el plugin:

Copia el archivo desde:
``` 
wp-content/plugins/clevers-product-carousel/templates/cards/card-1.php
```

a tu tema en:
```
wp-content/themes/tu-tema/clevers-product-carousel/cards/card-1.php
```

El sistema cargará automáticamente tu versión.

---

## 🪝 Hooks y filters para desarrolladores

Puedes extender el plugin con los siguientes hooks principales. Las firmas
muestran los argumentos que recibe tu callback y el tipo de retorno esperado.

### Filtros

#### `cleverspr_carousel_query_args` *(filter)*

Modifica los argumentos de `WC_Product_Query` antes de ejecutarla.
Útil para añadir restricciones de stock, precio máximo, visibilidad, etc.

```php
/**
 * @param array  $args        Argumentos de WC_Product_Query.
 * @param int    $carousel_id ID del carrusel.
 * @param array  $meta        Meta del carrusel guardado en post meta.
 * @return array
 */
add_filter( 'cleverspr_carousel_query_args', function( $args, $carousel_id, $meta ) {
    // Limitar a productos con precio entre $20 y $200, visibles en catálogo.
    $args['price_range'] = [ 20, 200 ];
    $args['visibility']  = 'visible';
    return $args;
}, 10, 3 );
```

#### `cleverspr_carousel_css_vars` *(filter)*

Modifica el arreglo de variables CSS que se imprimen inline por carrusel.
Cada entrada es un string `"--nombre-var: valor;"`.

```php
/**
 * @param array $vars       Variables CSS finales que se imprimirán.
 * @param int   $carousel_id
 * @param array $settings   Configuración del carrusel.
 * @param array $vars_map   Mapa setting_key => nombre de variable CSS.
 * @return array
 */
add_filter( 'cleverspr_carousel_css_vars', function( $vars, $carousel_id, $settings, $vars_map ) {
    // Añadir una variable personalizada (útil para CSS propio en el tema).
    $vars[] = '--clevers-margen-cards: 24px;';
    $vars[] = '--clevers-radio-cards: 12px;';
    return $vars;
}, 10, 4 );
```

#### `cleverspr_carousel_template_path` *(filter)*

Ajusta la ruta relativa usada para localizar templates dentro de `templates/`.

```php
/**
 * @param string $rel_path Ruta actual (p.ej. "carousels/carousel-1.php").
 * @return string
 */
add_filter( 'cleverspr_carousel_template_path', function( $rel_path ) {
    // Forzar un template alternativo para todos los carruseles.
    return str_replace( 'carousels/carousel-', 'carousels/custom-', $rel_path );
} );
```

#### `cleverspr_carousel/cache_ttl` *(filter)*

Controla el TTL del transient que cachea el HTML renderizado del carrusel.

```php
add_filter( 'cleverspr_carousel/cache_ttl', function( $ttl, $carousel_id, $settings, $args ) {
    // Carruseles con filtro manual (productos elegidos a mano) pueden cachear más.
    if ( ! empty( $settings['manual_products_enabled'] ) ) {
        return HOUR_IN_SECONDS;
    }
    return $ttl;
}, 10, 4 );
```

### Acciones

#### `cleverspr_carousel_before_render` / `cleverspr_carousel_after_render` *(actions)*

Se ejecutan justo antes/después de incluir el template del carrusel. Útil
para inyectar markup, registrar JS extra o emitir métricas.

```php
add_action( 'cleverspr_carousel_before_render', function( $carousel_id, $settings, $products ) {
    // P.ej. anunciar el evento a tu sistema de analytics.
    do_action( 'mi_tracking/carousel_render_start', $carousel_id, count( $products ) );
}, 10, 3 );

add_action( 'cleverspr_carousel_after_render', function( $carousel_id, $settings, $products ) {
    do_action( 'mi_tracking/carousel_render_end', $carousel_id );
}, 10, 3 );
```

> **Formato namespaced:** también están disponibles las variantes con `/`
> — `cleverspr_carousel/query_args`, `cleverspr_carousel/cache_ttl`,
> `cleverspr_carousel/before`, `cleverspr_carousel/after`,
> `cleverspr_carousel/products`, `cleverspr_carousel/rendered_html`. Las firmas
> son equivalentes; elegí la convención que prefieras.

---


## 🔒 Política de merges en `main`

Para mantener un historial limpio y auditable:

- `main` debe aceptar cambios **solo vía Pull Request**.
- Configura el repositorio para permitir únicamente **Squash merge** o **Rebase merge**.
- Evita merges tipo `Create a merge commit` (incluye merges de ramas remote-tracking).
- Marca como requerido el workflow **Main History Guard** para bloquear pushes directos a `main`.

## 📷 Capturas

1. Configuración del slider en el panel de administración.  
2. Slider de productos en frontend.  
3. Colores configurables por carrusel.  
4. Ejemplos de presets personalizados.

---

## 🧩 Requisitos

- WordPress 5.8+
- WooCommerce 6.0+
- PHP 7.4 o superior

---

## 🧱 Estructura del plugin

``` 
clevers-product-carousel/
├── clevers-product-carousel.php
├── assets/
│   ├── carousel.css
│   ├── carousel.js
│   ├── block.js
├── templates/
│   ├── carousels/
│   └── cards/
└── languages/
```

---

## 📒 Changelog

### 1.2.3

- Enhanced Brizy editor integration.
- Refined JSON import flow.
- Improved frontend fallback behavior.
- Added Gutenberg block support improvements.
- Added REST API endpoints for carousel operations.
- Enhanced admin tools and management UX.

## 📜 Licencia

GPLv2 o posterior  
https://www.gnu.org/licenses/gpl-2.0.html

---

## 🧩 Créditos

Desarrollado con ❤️ por [Clevers Devs](https://clevers.dev)

¿Quieres contribuir o sugerir mejoras?  
Abre un issue o PR en [github.com/agenciaingenium/Clevers-Woocommerce-Product-Carousel](https://github.com/agenciaingenium/Clevers-Woocommerce-Product-Carousel)
