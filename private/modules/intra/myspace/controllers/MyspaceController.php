<?php
class MySpaceController {
    public function render(): string {
        ob_start(); ?>
        <div class="p-4">
          <h2>Mi espacio</h2>
          <p>Bienvenido/a a tu espacio personal. (contenido provisional)</p>
        </div>
        <?php
        return ob_get_clean();
    }
}
