<%-- Modal content of the row action "Mark as done" (TaskController::done()). --%>
<div class="fdk-modal-inner">
    <h3 class="font-bold text-lg mb-4">Mark as done?</h3>
    <p class="mb-4">$Task.Title <span class="text-base-content/60">($Task.Project.Title)</span></p>
    <form method="post" action="$Action" class="flex gap-2 justify-end">
        <input type="hidden" name="SecurityID" value="$Token">
        <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
        <button type="submit" class="btn btn-primary">Done</button>
    </form>
</div>
