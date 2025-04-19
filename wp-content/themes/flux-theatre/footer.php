    <footer id="colophon" class="site-footer">
        <div class="site-info">
            <?php
            printf(
                esc_html__('© %1$s %2$s', 'flux-theatre'),
                date('Y'),
                get_bloginfo('name')
            );
            ?>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html> 