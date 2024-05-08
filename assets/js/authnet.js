(function (Drupal) {
  Drupal.behaviors.customModuleBehavior = {
    attach: function (context, settings) {
      // Get the form element.
      let form = document.querySelector('#authnet-cim-manager-cim-creation-fom');

      // Get the input field for the card number.
      let cardNumberInput = form.querySelector('#edit-card-number');

      // Function to format card number as the user types.
      function formatCardNumber(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '').substring(0, 16);

        // Add spaces every 4 digits
        input.value = value.replace(/(\d{4})/g, '$1 ').trim();
      }

      // Attach an event listener for the input event.
      cardNumberInput.addEventListener('input', formatCardNumber);

      // Get the input field for the expiry date.
      let expiryDateInput = form.querySelector('#edit-expiry-date');

      // Function to format expiry date as the user types.
      function formatExpiryDate(event) {
        let input = event.target;
        let value = input.value.replace(/\D/g, '').substring(0, 4);

        // Add a slash between month and year.
        input.value = value.replace(/(\d{2})(\d)/, '$1/$2');
      }

      // Attach an event listener for the input event.
      expiryDateInput.addEventListener('input', formatExpiryDate);

      // Get the input field for the CVV.
      let cvvInput = form.querySelector('#edit-cvv');

      // Function to validate and format CVV as the user types.
      function formatCVV(event) {
        let input = event.target;

        // Update the input value with the formatted CVV.
        input.value = input.value.replace(/\D/g, '').substring(0, 4);
      }

      // Attach an event listener for the input event.
      cvvInput.addEventListener('input', formatCVV);

      // Get element zip code.
      let zipInput = form.querySelector('#edit-zip');

      // Function to validate and format CVV as the user types.
      function formatZip(event) {
        let input = event.target;

        // Remove non-numeric characters and limit to 4 digits.
        input.value = input.value.replace(/\D/g, '').substring(0, 6);
      }

      // Attach an event listener for the input event
      zipInput.addEventListener('input', formatZip);
      let addressFields = form.querySelector('#edit-city, #edit-state, #edit-country');

      let inputPhone = document.querySelector('#edit-phone');
      if (inputPhone) {
        inputPhone.addEventListener('input', function () {
          // Remove any characters that are not numbers, plus sign, hyphen, parentheses, or space
          this.value = this.value.replace(/[^0-9()+\- \s]/g, '');

          // Limit the input to a maximum of 15 characters
          if (this.value.length > 18) {
            this.value = this.value.slice(0, 18);
          }
        });
      }

      // Function to validate and format zip code as the user types.
      function formatAddress(event) {
        let inputValue = event.value;
        if (!/^[a-zA-Z\s]*$/.test(inputValue)) {
          event.preventDefault();
        }
      }

      // Add event listener to the zip code input field.
      addressFields.addEventListener('input', formatAddress);
    }
  }
})(Drupal);

