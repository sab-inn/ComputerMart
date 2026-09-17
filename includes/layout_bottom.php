    </main>
    <script>
        document.body.addEventListener('htmx:configRequest', function (evt) {
            evt.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;
        });
    </script>
</body>
</html>
