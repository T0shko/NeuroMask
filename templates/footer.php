    </main><!-- /.main-content -->
</div><!-- /.app-layout -->

<!-- Global JavaScript -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
<script src="<?= assetUrl('js/app.js') ?>"></script>

<?php if (!empty($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
    <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
