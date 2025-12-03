<?php include_once("header.php")?>
<div class="container">

<div style="max-width: 800px; margin: 10px auto">
  <h2 class="my-3">Create new auction</h2>
  <div class="card">
    <div class="card-body">

      <form id="createAuctionForm" method="post" action="create_auction_result.php" enctype="multipart/form-data">

        <!-- Title -->
        <div class="form-group row">
          <label for="auctionTitle" class="col-sm-2 col-form-label text-right">Title of auction</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="auctionTitle" name="title" placeholder="e.g. Black mountain bike">
            <small class="form-text text-muted">
              <span class="text-danger">* Required.</span> A short description of the item you're selling.
            </small>
          </div>
        </div>

        <!-- Details -->
        <div class="form-group row">
          <label for="auctionDetails" class="col-sm-2 col-form-label text-right">Details</label>
          <div class="col-sm-10">
            <textarea class="form-control" id="auctionDetails" name="description" rows="4"></textarea>
            <small class="form-text text-muted">Full details of the item you're auctioning.</small>
          </div>
        </div>

        <!-- Category -->
        <div class="form-group row">
          <label for="auctionCategory" class="col-sm-2 col-form-label text-right">Category</label>
          <div class="col-sm-10">
            <!-- <select class="form-control" id="auctionCategory" name="category">
              <option value="">Choose...</option>
              <option value="fill">Fill me in</option>
              <option value="with">with options</option>
              <option value="populated">populated from a database?</option>
            </select> -->
            <select class="form-control" id="auctionCategory" name="category">
              <option value="" disabled selected>Choose...</option>
              <option value="electronics">Electronics</option>
              <option value="fashion">Fashion & Accessories</option>
              <option value="home">Home & Kitchen</option>
              <option value="sports">Sports & Outdoors</option>
              <option value="toys">Toys & Games</option>
              <option value="collectibles">Collectibles & Art</option>
              <option value="books">Books & Media</option>
              <option value="automotive">Automotive</option>
              <option value="beauty">Beauty & Personal Care</option>
              <option value="other">Other</option>
            </select>

            <small class="form-text text-muted">
              <span class="text-danger">* Required.</span> Select a category.
            </small>
          </div>
        </div>

        <!-- Starting price -->
        <div class="form-group row">
          <label for="auctionStartPrice" class="col-sm-2 col-form-label text-right">Starting price</label>
          <div class="col-sm-10">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" class="form-control" id="auctionStartPrice" name="startprice">
            </div>
            <small class="form-text text-muted">
              <span class="text-danger">* Required.</span> Initial bid amount.
            </small>
          </div>
        </div>

        <!-- Reserve Price-->
        <div class="form-group row">
          <label for="auctionReservePrice" class="col-sm-2 col-form-label text-right">Reserve price</label>
          <div class="col-sm-10">
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">£</span>
              </div>
              <input type="number" class="form-control" id="auctionReservePrice" placeholder="Optional">
            </div>
            <small class="form-text text-muted">
              This reserve price field is visual only and <strong>will not be saved</strong>.
            </small>
          </div>
        </div>


        <!-- Image upload -->
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-right">Item Image</label>
          <div class="col-sm-10">
            <input type="file" class="form-control-file" name="itemImage" accept="image/*">
            <small class="form-text text-muted">Upload an image of your item (optional).</small>
          </div>
        </div>


        <!-- End date -->
        <div class="form-group row">
          <label for="auctionEndDate" class="col-sm-2 col-form-label text-right">End date</label>
          <div class="col-sm-10">
            <input type="datetime-local" class="form-control" id="auctionEndDate" name="enddate">
            <small class="form-text text-muted">
              <span class="text-danger">* Required.</span> Day for the auction to end.
            </small>
          </div>
        </div>

        <div class="d-flex" style="gap:8px;">
          <button type="button" id="previewBtn" class="btn btn-secondary">Preview</button>
          <button type="submit" class="btn btn-primary flex-fill">Create Auction</button>
        </div>

      </form>

      <!-- Preview Modal -->
      <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="previewModalLabel">Auction Preview</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <img id="previewImage" src="" alt="No image" class="img-fluid img-thumbnail" style="display:none; max-height:360px; object-fit:contain;"/>
                </div>
                <div class="col-md-6">
                  <h4 id="previewTitle" class="mb-2"></h4>
                  <p id="previewDescription"></p>
                  <p><strong>Category:</strong> <span id="previewCategory"></span></p>
                  <p><strong>Starting price:</strong> £<span id="previewStartPrice"></span></p>
                  <p><strong>End date:</strong> <span id="previewEndDate"></span></p>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" id="confirmSubmit" class="btn btn-primary">Submit auction</button>
            </div>
          </div>
        </div>
      </div>

      <script>
        (function(){
          const previewBtn = document.getElementById('previewBtn');
          const confirmSubmit = document.getElementById('confirmSubmit');
          const form = document.getElementById('createAuctionForm');

          function formatDateTimeLocal(value) {
            if (!value) return '';
            try {
              const d = new Date(value);
              if (isNaN(d)) return value;
              return d.toLocaleString();
            } catch (e) {
              return value;
            }
          }

          function showModal() {
            const modalEl = document.getElementById('previewModal');
            if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
              const modal = new bootstrap.Modal(modalEl);
              modal.show();
            } else if (window.jQuery && typeof $(modalEl).modal === 'function') {
              $(modalEl).modal('show');
            } else {
              modalEl.style.display = 'block';
            }
          }

          previewBtn.addEventListener('click', function(e){
            // gather values
            const title = document.getElementById('auctionTitle').value;
            const description = document.getElementById('auctionDetails').value;
            const categorySelect = document.getElementById('auctionCategory');
            const category = categorySelect.options[categorySelect.selectedIndex] ? categorySelect.options[categorySelect.selectedIndex].text : '';
            const startprice = document.getElementById('auctionStartPrice').value;
            const enddate = document.getElementById('auctionEndDate').value;

            document.getElementById('previewTitle').textContent = title || '(No title)';
            document.getElementById('previewDescription').textContent = description || '(No description)';
            document.getElementById('previewCategory').textContent = category || '(No category)';
            document.getElementById('previewStartPrice').textContent = startprice || '(Not set)';
            document.getElementById('previewEndDate').textContent = formatDateTimeLocal(enddate) || '(Not set)';

            // image preview
            const fileInput = document.querySelector('input[name="itemImage"]');
            const img = document.getElementById('previewImage');
            if (fileInput && fileInput.files && fileInput.files[0]) {
              const reader = new FileReader();
              reader.onload = function(ev){
                img.src = ev.target.result;
                img.style.display = 'block';
              }
              reader.readAsDataURL(fileInput.files[0]);
            } else {
              img.style.display = 'none';
              img.src = '';
            }

            showModal();
          });

          // when confirm submit clicked, submit the form
          confirmSubmit.addEventListener('click', function(){
            form.submit();
          });
        })();
      </script>

    </div>
  </div>
</div>

</div>
<?php include_once("footer.php")?>
