<?php

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) {
    die('Illegal request');
}

use OsdAurox\Sec;

?>
<hr>
<footer class="footer text-center py-3">
    <p>&copy; <?= Sec::hNoHtml(date('Y')); ?> Mon Site. Tous droits réservés. <br>
        Powered by <a href="https://aurox.fr">Aurox</a>🚀️ - Another Brutalism Design Library</p>
</footer>

</div> <!-- fin de container -->

<!-- Ajoutez vos scripts -->
<script src="script.js"></script>
</body>
</html>
