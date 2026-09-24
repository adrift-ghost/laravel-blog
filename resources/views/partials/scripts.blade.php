<script>
(function () {
    // Confirm destructive actions: <form data-confirm="Are you sure?">
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) { e.preventDefault(); }
    });

    // Live unique slug preview: <input data-slug-source="#title" data-slug-model="post" data-slug-ignore="12">
    var slugUrl = @json(blog_route('slug'));
    document.querySelectorAll('[data-slug-model]').forEach(function (slug) {
        var source = document.querySelector(slug.getAttribute('data-slug-source'));
        var hint = document.querySelector(slug.getAttribute('data-slug-hint'));
        var auto = document.querySelector(slug.getAttribute('data-slug-auto'));
        var timer;

        function update() {
            var custom = auto && !auto.checked;
            slug.readOnly = !custom;
            var value = custom ? slug.value : (source ? source.value : '');
            clearTimeout(timer);
            timer = setTimeout(function () {
                if (!value) { if (!custom) slug.value = ''; if (hint) hint.textContent = ''; return; }
                var params = new URLSearchParams({ model: slug.getAttribute('data-slug-model'), value: value });
                if (slug.getAttribute('data-slug-ignore')) params.set('ignore', slug.getAttribute('data-slug-ignore'));
                fetch(slugUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!custom) slug.value = d.slug;
                        if (hint) hint.textContent = custom && d.slug !== slug.value ? 'Will be saved as: ' + d.slug : '';
                    }).catch(function () {});
            }, 250);
        }

        if (source) source.addEventListener('input', function () { if (!auto || auto.checked) update(); });
        slug.addEventListener('input', update);
        if (auto) auto.addEventListener('change', update);
        if (auto) slug.readOnly = auto.checked;
    });

    // Post type switcher: show only the sections relevant to the chosen type.
    var typeInputs = document.querySelectorAll('input[name="type"]');
    if (typeInputs.length) {
        var meta = JSON.parse(document.getElementById('blog-type-meta').textContent);
        function applyType() {
            var checked = document.querySelector('input[name="type"]:checked');
            if (!checked) return;
            var t = meta[checked.value];
            document.querySelectorAll('[data-type-fields]').forEach(function (el) {
                var on = el.getAttribute('data-type-fields') === checked.value;
                el.hidden = !on;
                el.querySelectorAll('input,select,textarea').forEach(function (i) { i.disabled = !on; });
            });
            var cover = document.getElementById('cover-section');
            if (cover) {
                cover.hidden = t.cover_image === 'none';
                var req = document.getElementById('cover-required');
                if (req) req.hidden = t.cover_image !== 'required';
            }
            var video = document.getElementById('video-section');
            if (video) {
                video.hidden = t.video === 'none';
                video.querySelectorAll('input').forEach(function (i) { i.disabled = t.video === 'none'; });
            }
            var creq = document.getElementById('content-required');
            if (creq) creq.hidden = !t.content_required;
        }
        typeInputs.forEach(function (i) { i.addEventListener('change', applyType); });
        applyType();
    }

    // Image preview before upload
    document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            var img = document.querySelector(input.getAttribute('data-preview'));
            if (img && input.files && input.files[0]) { img.src = URL.createObjectURL(input.files[0]); img.hidden = false; }
        });
    });
})();
</script>
