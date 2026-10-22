<script>
    const SERVER_MODE = true;
    const SERVER_LABEL = @json($label);
    const X = @json($X);
    const REKON = [];
    const CAN_FIX = @json($canFix ?? false);
    const FIX_URL = @json(route('fingrab.kecocokan.fix'));
    const CSRF = @json(csrf_token());
</script>