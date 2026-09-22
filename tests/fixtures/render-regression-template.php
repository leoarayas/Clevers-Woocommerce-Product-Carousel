<?php

$attrs = clevprca_get_slider_data_attributes( $carousel_id, $settings );

echo '<div class="render-regression" data-carousel-id="' . (int) $carousel_id . '">';
echo '<div class="slick-carousel" ' . $attrs . '></div>';
echo '</div>';
