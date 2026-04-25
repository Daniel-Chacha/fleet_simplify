<?php
// Password requirements checklist. Drop this immediately after a password
// input that has data-pw-rules pointing at the block's id.
// Usage: set $rules_id then include this file.
$rules_id = $rules_id ?? 'pw-rules';
?>
<div id="<?= e($rules_id) ?>" class="pw-rules" aria-live="polite">
    <span class="pw-rule" data-rule="length"><span class="rule-mark">✓</span>At least 8 characters</span>
    <span class="pw-rule" data-rule="upper"><span class="rule-mark">✓</span>One uppercase letter</span>
    <span class="pw-rule" data-rule="lower"><span class="rule-mark">✓</span>One lowercase letter</span>
    <span class="pw-rule" data-rule="number"><span class="rule-mark">✓</span>One number</span>
    <span class="pw-rule" data-rule="special"><span class="rule-mark">✓</span>One special character</span>
</div>
