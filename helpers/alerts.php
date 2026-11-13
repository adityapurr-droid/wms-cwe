<?php
/**
 * Helper Flash Message dan Alert
 */

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void {
        $_SESSION['flash_alert'] = [
            'type'    => $type, // success, error, warning, info
            'message' => $message
        ];
    }
}

if (!function_exists('render_flash')) {
    function render_flash(): string {
        $html = '';
        if (isset($_SESSION['flash_alert'])) {
            $type = $_SESSION['flash_alert']['type'];
            $msg_js = addslashes($_SESSION['flash_alert']['message']);
            unset($_SESSION['flash_alert']);

            $icon = 'info-circle';
            $bs_class = 'alert-info';
            if ($type === 'success') {
                $icon = 'check-circle';
                $bs_class = 'alert-success';
            } elseif ($type === 'error' || $type === 'danger') {
                $icon = 'exclamation-triangle';
                $bs_class = 'alert-danger';
                $type = 'error';
            } elseif ($type === 'warning') {
                $icon = 'exclamation-circle';
                $bs_class = 'alert-warning';
            }

            $html = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: '{$type}',
                            title: 'Pemberitahuan',
                            text: '{$msg_js}',
                            timer: 3500,
                            timerProgressBar: true,
                            confirmButtonColor: '#198754'
                        });
                    }
                });
            </script>
            ";

            if ($type !== 'success') {
                $msg_html = htmlspecialchars($msg_js);
                $html .= "
                <div class=\"alert {$bs_class} alert-dismissible fade show shadow-sm\" role=\"alert\">
                    <i class=\"bi bi-{$icon} me-2\"></i> {$msg_html}
                    <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
                </div>
                ";
            }
        }

        // Kompatibilitas dengan flash_error sederhana
        if (isset($_SESSION['flash_error'])) {
            $msg = htmlspecialchars($_SESSION['flash_error']);
            unset($_SESSION['flash_error']);
            $html .= "
            <div class=\"alert alert-danger alert-dismissible fade show shadow-sm\" role=\"alert\">
                <i class=\"bi bi-exclamation-triangle me-2\"></i> {$msg}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
            </div>
            ";
        }

        return $html;
    }
}

