document.addEventListener("DOMContentLoaded", function() {
  // Find the site title element
  var siteTitle = document.querySelector(".site-headings");
  console.log('heloo')
  // Check if the element exists
  if (siteTitle) {
    // Add a click event listener
    siteTitle.addEventListener("click", function(event) {
      // Prevent the default behavior if any
      event.preventDefault();

      // Open the link in the same tab
      window.location.href = window.location.origin;
    });
  }
});