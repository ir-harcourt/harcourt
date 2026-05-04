<footer class="b-footer">
  <div class="grid-bg">


      <div class="container-responsive">
       
          <div style="margin-left: 0; margin-right: 0;" class="row">

            <div class="col-lg-6 col-md-12 col-sm-12 footer-logo">
                  <div class="alt-h logo d-flex align-items-center justify-content-start">
                    <?php the_custom_logo(); ?>
                  </div>
            </div>

              
              <div class="col-lg-6 col-md-12 col-sm-12 force-nav remove-row">
				  <div class="d-flex align-items-center justify-content-end">
					  <nav class="footer-nav">
                		<?php dynamic_sidebar('footer-widget-area'); ?>
              		  </nav>
				  </div>
              </div>
            </div>
</div>
</div>




<?php 
  $pdf1 = get_field('pdf1', 'options');
  $pdf2 = get_field('pdf2', 'options');
  $copyright = get_field('copyright', 'options');
  $terms = get_field('terms', 'options');
  $privacy = get_field('privacy', 'options');
  $legal = get_field('legal', 'options');
?>


      <div class="row remove-row">
         <div class="col-md-12 col-lg-6 social-half">
         <!-- <div class="new-socials">
                            <a href="https://www.facebook.com/harcourtco/" target="_blank"><i class="fab fa-facebook-square"></i></a> 
                              <a href="https://www.linkedin.com/company/harcourt-industrial-inc-" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                              <a href="https://twitter.com/theharcourtco" target="_blank"><i class="fab fa-twitter"></i></a>
                            <a href="#" target="_blank"><i class="fab fa-instagram"></i></a> 
                      </div> -->
        </div>
        <div class="col-md-12 col-lg-6 iso-half">
          
          <div class="iso itar">
			  <i class="fas fa-award"></i> <strong class="iso-cert">ITAR Registered</strong>
			</div>
			
			<div class="iso">
			  <i class="fas fa-award"></i> <a target="_blank" href="/wp-content/uploads/2023/11/Certificate.pdf"><strong class="iso-cert">ISO Certified</strong> <span class="iso-spec">9001:2015</span></a>
          <!-- <div class="iso"><a target="_blank" href="/wp-content/uploads/2022/04/Harcourt-Industrial-Inc.-Terms-Conditions-of-Sale_April-2022-1.pdf"><strong class="iso-cert">Terms & Conditions of Sale</strong></a></div> -->
         

        </div>
      </div>
</div>
</div>



      <div style="width: 100%;" class="row remove-row">
        <div style="width: 100%;" class="bottom-section d-flex">
        <div class="col-lg-3 col-md-12 col-sm-12 copy-sec">
          <div class="copyRight">
          <?php echo $copyright ?> 
          </div>
      </div>


      <div class="col-lg-9 col-md-12 col-sm-12 terms-sec">
          <div class="ml-auto terms-section">
          <?php if (ICL_LANGUAGE_CODE != 'fr') { ?><a href="<?php echo home_url('/'); ?>wp-content/uploads/2023/12/Harcourt-Supplier-Terms-Conditions-2023.pdf" target="_blank"><?php echo $pdf1 ?></a> <?php } ?>
			  <?php if (ICL_LANGUAGE_CODE == 'fr') { ?><a href="https://www.harcourt.co/wp-content/uploads/2025/07/Harcourt_Supplier-terms_French.pdf" target="_blank"><?php echo $pdf1 ?></a> <?php } ?>
           <?php if (ICL_LANGUAGE_CODE != 'fr') { ?><a href="<?php echo home_url('/'); ?>wp-content/uploads/2023/12/Harcourt-Terms-Conditions-of-Purchase-2023.pdf" target="_blank"><?php echo $pdf2 ?></a><?php } ?>
			  <?php if (ICL_LANGUAGE_CODE == 'fr') { ?><a href="https://www.harcourt.co/wp-content/uploads/2025/07/Harcourt_terms-and-conditions-of-sales_French.pdf" target="_blank"><?php echo $pdf2 ?></a> <?php } ?>
          <?php if (ICL_LANGUAGE_CODE != 'fr') { ?><a href="<?php echo home_url('/'); ?>wp-content/uploads/2025/03/Harcourt-Terms-and-Conditions-of-Sale-2025.pdf" target="_blank"><?php echo $terms ?></a><?php } ?>
		  <?php if (ICL_LANGUAGE_CODE == 'fr') { ?><a href="https://www.harcourt.co/wp-content/uploads/2025/07/Harcourt_General-terms_French.pdf" target="_blank"><?php echo $terms ?></a> <?php } ?>
		  <a href="<?php echo home_url('/'); ?>privacy"><?php echo $privacy?></a>
		  <a href="<?php echo home_url('/'); ?>legal"><?php echo $legal ?></a>
          </div>
</div>
      </div>

    </div>

  


  <!-- Modal -->

</footer>


<?php wp_footer(); ?>

<div class="modal fade" id="formPopout" tabindex="-1" role="dialog" aria-labelledby="formPopoutLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <span class="close-btn" data-dismiss="modal" aria-label="Close"><i class="fal fa-times-hexagon"></i></span>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-5 black-radial-gradient modal-side d-flex align-items-center">
              <!-- <div>
                <h3>Request Access to Join</h3>
                <div class="list">
                  <ul>
                    <li>Access to product catalog of over 100+ products.</li>
                    <li>Product Configurator you can't get anywhere else.</li>
                    <li>No fees, industry professionals only.</li>
                    <li>Download CAD files in all the latest formats.</li>
                  </ul>
                </div>
              </div> -->
            </div>
            <div class="col-lg-7 silver-radial-gradient form">
              <div class="text-center">
                <img src="https://devharcourt.wpengine.com/wp-content/uploads/2019/10/elite-logo.png" alt="">
              </div>
              <?php gravity_form(1, false, false, false, '', true); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
AOS.init({
  disable: 'mobile',
  once: true,
});
</script>
</body>
</html>
