<?php
/**
 * Footer-Include
 */

?>
    </main>
    
    <footer class="footer">
        <div class="footer-container">
            <p>&copy; <?php echo date('Y'); ?> Kanuclub CJD Kaltenstein Vaihingen/Enz</p>
            <p>
                <a href="https://kccjd.de" target="_blank">Besuchen Sie unsere Hauptseite</a>
            </p>
        </div>
    </footer>
    
    <script src="<?php echo strpos($_SERVER['PHP_SELF'], 'admin') !== false ? '../assets/js/main.js' : 'assets/js/main.js'; ?>"></script>
</body>
</html>
