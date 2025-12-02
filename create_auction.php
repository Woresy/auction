<?php include_once("header.php")?>
<div class="container">

<div style="max-width: 800px; margin: 10px auto">
  <h2 class="my-3">Create new auction</h2>
  <div class="card">
    <div class="card-body">

      <form method="post" action="create_auction_result.php" enctype="multipart/form-data">

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

        <button type="submit" class="btn btn-primary form-control">Create Auction</button>

      </form>

    </div>
  </div>
</div>

</div>
<?php include_once("footer.php")?>
