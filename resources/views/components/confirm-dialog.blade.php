<dialog id="confirm-dialog" class="confirm-dialog modal-card" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
    <form method="dialog">
        <h2 id="confirm-dialog-title" data-confirm-title>Please confirm</h2>
        <p id="confirm-dialog-message" data-confirm-message></p>
        <dl data-confirm-details hidden></dl>
        <label data-confirm-review-field hidden class="my-4 text-sm text-slate-500 dark:text-slate-400"><input type="checkbox" data-confirm-review-checkbox class="mr-2 align-middle">I have reviewed and verified that all information is correct.</label>
        <div class="modal-actions">
            <button type="submit" value="cancel" class="secondary-button" autofocus>Cancel</button>
            <button type="submit" value="confirm" class="primary-button" data-confirm-accept>Confirm</button>
        </div>
    </form>
</dialog>
