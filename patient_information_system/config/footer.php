<footer id="animatedFooter" class="main-footer fixed-bottom shadow-lg">
    <strong>Copyright &copy; <?php echo date('Y');?> 
    <a href="./" class="text-primary">Aroroy Patient Management System</a>.</strong> 
    All rights reserved.
    
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.0
    </div>
  </footer>

  <div id="footerToggle" class="footer-toggle-btn" onclick="toggleFooter()">
    <i id="footerIcon" class="fas fa-chevron-down"></i> 
    <span id="footerText" class="ml-1">Hide Footer</span>
  </div>

  <aside class="control-sidebar control-sidebar-dark">
    </aside>

  <style>
    /* Footer Layout & Animation */
    .main-footer.fixed-bottom {
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 1030; /* Just below modals but above content */
      background: #ffffff;
      border-top: 1px solid #dee2e6;
      padding: 15px 20px;
    }

    /* State when hidden: slide completely off-screen */
    .footer-hidden {
      transform: translateY(100%);
    }

    /* Floating Toggle Tab */
    .footer-toggle-btn {
      position: fixed;
      bottom: 0;
      right: 30px;
      z-index: 1040;
      background: linear-gradient(45deg, #4e73df, #224abe);
      color: #fff;
      padding: 6px 15px;
      border-radius: 8px 8px 0 0;
      cursor: pointer;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      box-shadow: 0 -2px 10px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .footer-toggle-btn:hover {
      background: #224abe;
      padding-bottom: 10px; /* Slight lift on hover */
    }

    /* Keep the toggle visible even when footer is hidden */
    .footer-hidden ~ #footerToggle {
      background: #1a267d;
    }

    /* Adjust page content padding so footer doesn't overlap last row initially */
    body.layout-fixed .content-wrapper {
        padding-bottom: 60px;
    }
  </style>

  <script>
    /**
     * Toggles the visibility of the fixed footer
     * Slides it down to provide a "clear vision" of the page background/content
     */
    function toggleFooter() {
        const footer = document.getElementById('animatedFooter');
        const icon = document.getElementById('footerIcon');
        const text = document.getElementById('footerText');
        const toggleBtn = document.getElementById('footerToggle');

        // Toggle the hidden class
        footer.classList.toggle('footer-hidden');

        // Update UI elements based on state
        if (footer.classList.contains('footer-hidden')) {
            // Change to "Show" state
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            text.innerText = "Show Footer";
            
            // Optional: Reduce opacity of the button when footer is hidden to be less distracting
            toggleBtn.style.opacity = "0.8";
        } else {
            // Change to "Hide" state
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            text.innerText = "Hide Footer";
            toggleBtn.style.opacity = "1";
        }
    }

    // Auto-hide footer on smaller mobile screens to save space
    if (window.innerWidth < 576) {
        setTimeout(toggleFooter, 1000); 
    }
  </script>