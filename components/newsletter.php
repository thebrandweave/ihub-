<section class="py-5 container-fluid">
  <div class="container">
    <div class="newsletter d-flex flex-column flex-lg-row justify-content-between align-items-center gap-4 bg-primary text-white p-4 p-lg-5 rounded-4 shadow-sm">
      <div>
        <h3 class="fw-bold mb-1">Join newsletter — 10% off first order</h3>
        <p class="mb-0 opacity-75">Exclusive deals & gear guides weekly.</p>
      </div>
      
      <div class="w-100" style="max-width: 450px;">
        <form id="newsletterForm" class="d-flex gap-2">
          <input 
            class="form-control border-0 px-3" 
            type="email" 
            name="subscriber_email" 
            placeholder="Enter your email" 
            required
          >
          <button type="submit" class="btn btn-dark px-4 fw-bold">Subscribe</button>
        </form>
        <div id="newsletterStatus" class="mt-2 small fw-semibold"></div>
      </div>
    </div>
  </div>
</section>

<script>
document.getElementById('newsletterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const statusDiv = document.getElementById('newsletterStatus');
    const emailInput = this.querySelector('input[name="subscriber_email"]');
    const btn = this.querySelector('button');
    
    // UI Loading State
    statusDiv.textContent = "Processing...";
    btn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('email', emailInput.value);

        const response = await fetch('<?= $BASE_URL ?>api/subscribe.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            statusDiv.className = "mt-2 small text-white animate__animated animate__fadeIn";
            statusDiv.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${result.message}`;
            this.reset();
        } else {
            statusDiv.className = "mt-2 small text-warning";
            statusDiv.textContent = result.message;
        }
    } catch (error) {
        statusDiv.className = "mt-2 small text-danger";
        statusDiv.textContent = "Something went wrong. Please try again later.";
    } finally {
        btn.disabled = false;
    }
});
</script>