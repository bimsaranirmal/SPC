// Define pharmacy location data
const pharmacyLocations = [
    {
      name: "Colombo - Main Branch",
      address: "75, Sir Baron Jayatilaka Mawatha, Colombo 01",
      phone: "+94 11 232 5171",
      hours: "8:30 AM - 8:30 PM (Daily)",
      coordinates: { lat: 6.9271, lng: 79.8612 },
      directions: "https://maps.app.goo.gl/Ww8NB3UmcnMYG2y78"
    },
    {
      name: "Kandy",
      address: "14 Lamagaraya Rd, Kandy",
      phone: "+94 81 221 5678",
      hours: "8:30 AM - 8:00 PM (Daily)",
      coordinates: { lat: 7.2906, lng: 80.6337 },
      directions: "https://maps.app.goo.gl/4vaSddL2SzAbikTk8"
    },
    {
      name: "Galle",
      address: "42, Wakwella Road, Galle",
      phone: "+94 91 224 3456",
      hours: "8:30 AM - 7:30 PM (Daily)",
      coordinates: { lat: 6.0535, lng: 80.2210 },
      directions: "https://maps.app.goo.gl/uD83U7Cvxi7GLqW98"
    },
    {
      name: "Jaffna",
      address: "125, Hospital Road, Jaffna",
      phone: "+94 21 222 7890",
      hours: "8:30 AM - 7:00 PM (Daily)",
      coordinates: { lat: 9.6615, lng: 80.0255 },
      directions: "https://maps.app.goo.gl/Hxr3oWbdLDxumMxh6"
    },
    {
      name: "Negombo",
      address: "78, Lewis Place, Negombo",
      phone: "+94 31 223 4567",
      hours: "8:30 AM - 8:00 PM (Daily)",
      coordinates: { lat: 7.2080, lng: 79.8358 },
      directions: "https://maps.app.goo.gl/xc21wPadYJbbFoEF8"
    },
    {
      name: "Matara",
      address: "35, Anagarika Dharmapala Mawatha, Matara",
      phone: "+94 41 222 8901",
      hours: "8:30 AM - 7:30 PM (Daily)",
      coordinates: { lat: 5.9549, lng: 80.5550 },
      directions: "https://maps.app.goo.gl/EFgfEUrQEdtEEcWq8"
    }
  ];
  
  // Wait for the DOM to be fully loaded
  document.addEventListener('DOMContentLoaded', function() {
    // Get search button and input elements
    const searchButton = document.querySelector('.search-btn');
    const searchInput = document.querySelector('.search-box input');
    
    // Add event listener for search button click
    searchButton.addEventListener('click', function() {
      searchPharmacies();
    });
    
    // Add event listener for Enter key press in search input
    searchInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        searchPharmacies();
      }
    });
    
    // Function to create and display the popup
    function createPopup(results) {
      // Remove any existing popup
      const existingPopup = document.querySelector('.search-results-popup');
      if (existingPopup) {
        existingPopup.remove();
      }
      
      // Create popup container
      const popup = document.createElement('div');
      popup.className = 'search-results-popup';
      popup.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        padding: 20px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        z-index: 1000;
      `;
      
      // Create popup header
      const header = document.createElement('div');
      header.style.cssText = `
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
      `;
      
      const title = document.createElement('h3');
      title.textContent = results.length > 0 ? 'Nearby Pharmacies' : 'No Results Found';
      title.style.margin = '0';
      
      const closeBtn = document.createElement('button');
      closeBtn.innerHTML = '&times;';
      closeBtn.style.cssText = `
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        padding: 0 5px;
      `;
      closeBtn.addEventListener('click', () => popup.remove());
      
      header.appendChild(title);
      header.appendChild(closeBtn);
      popup.appendChild(header);
      
      // Create results container
      if (results.length > 0) {
        const resultsContainer = document.createElement('div');
        
        results.forEach((pharmacy, index) => {
          const pharmacyCard = document.createElement('div');
          pharmacyCard.style.cssText = `
            padding: 15px;
            margin-bottom: 15px;
            background-color: ${index === 0 ? '#f0f8ff' : '#fff'};
            border-radius: 8px;
            border: 1px solid #eee;
            ${index === 0 ? 'border-left: 4px solid #4285f4;' : ''}
          `;
          
          // Add highlight for nearest pharmacy
          if (index === 0) {
            const nearestBadge = document.createElement('div');
            nearestBadge.textContent = 'Nearest';
            nearestBadge.style.cssText = `
              display: inline-block;
              background-color: #4285f4;
              color: white;
              padding: 3px 8px;
              border-radius: 4px;
              font-size: 12px;
              margin-bottom: 8px;
            `;
            pharmacyCard.appendChild(nearestBadge);
          }
          
          const name = document.createElement('h4');
          name.textContent = pharmacy.name;
          name.style.margin = '5px 0';
          
          const address = document.createElement('p');
          address.textContent = pharmacy.address;
          address.style.margin = '5px 0';
          
          const phone = document.createElement('p');
          phone.textContent = pharmacy.phone;
          phone.style.margin = '5px 0';
          
          const hours = document.createElement('p');
          hours.textContent = pharmacy.hours;
          hours.style.margin = '5px 0';
          
          const directionsLink = document.createElement('a');
          directionsLink.href = pharmacy.directions;
          directionsLink.textContent = 'Get Directions';
          directionsLink.target = '_blank';
          directionsLink.style.cssText = `
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #4285f4;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
          `;
          
          pharmacyCard.appendChild(name);
          pharmacyCard.appendChild(address);
          pharmacyCard.appendChild(phone);
          pharmacyCard.appendChild(hours);
          pharmacyCard.appendChild(directionsLink);
          
          resultsContainer.appendChild(pharmacyCard);
        });
        
        popup.appendChild(resultsContainer);
      } else {
        // No results message
        const noResults = document.createElement('p');
        noResults.textContent = 'No pharmacies found matching your search. Please try another location.';
        noResults.style.textAlign = 'center';
        popup.appendChild(noResults);
      }
      
      // Create overlay
      const overlay = document.createElement('div');
      overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 999;
      `;
      overlay.addEventListener('click', () => {
        popup.remove();
        overlay.remove();
      });
      
      // Add to document
      document.body.appendChild(overlay);
      document.body.appendChild(popup);
    }
    
    // Function to search pharmacies
    function searchPharmacies() {
      const searchTerm = searchInput.value.toLowerCase().trim();
      
      if (!searchTerm) {
        // Show all pharmacies if search is empty
        createPopup(pharmacyLocations);
        return;
      }
      
      // Filter pharmacies based on search term
      const results = pharmacyLocations.filter(pharmacy => {
        return pharmacy.name.toLowerCase().includes(searchTerm) || 
               pharmacy.address.toLowerCase().includes(searchTerm);
      });
      
      // Sort results by relevance (exact matches first)
      results.sort((a, b) => {
        const aNameMatch = a.name.toLowerCase().includes(searchTerm);
        const bNameMatch = b.name.toLowerCase().includes(searchTerm);
        
        if (aNameMatch && !bNameMatch) return -1;
        if (!aNameMatch && bNameMatch) return 1;
        return 0;
      });
      
      // Create and display popup with results
      createPopup(results);
    }
    
    // Optional: Add geolocation feature
    if (navigator.geolocation) {
      // Add a "Use My Location" button
      const locationButton = document.createElement('button');
      locationButton.textContent = 'Use My Location';
      locationButton.className = 'location-btn';
      locationButton.style.cssText = `
        margin-left: 10px;
        padding: 10px 20px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
      `;
      
      locationButton.addEventListener('click', function() {
        navigator.geolocation.getCurrentPosition(function(position) {
          const userLocation = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
          };
          
          // Calculate distance to each pharmacy
          const locationsWithDistance = pharmacyLocations.map(pharmacy => {
            const distance = calculateDistance(
              userLocation.lat,
              userLocation.lng,
              pharmacy.coordinates.lat,
              pharmacy.coordinates.lng
            );
            
            return {
              ...pharmacy,
              distance: distance
            };
          });
          
          // Sort by distance
          locationsWithDistance.sort((a, b) => a.distance - b.distance);
          
          // Display results
          createPopup(locationsWithDistance);
        }, function(error) {
          alert('Unable to get your location. Please enter your location manually.');
        });
      });
      
      // Add button after search button
      document.querySelector('.search-box').appendChild(locationButton);
    }
    
    // Calculate distance between two points using Haversine formula
    function calculateDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Radius of the earth in km
      const dLat = deg2rad(lat2 - lat1);
      const dLon = deg2rad(lon2 - lon1);
      const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2); 
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
      const distance = R * c; // Distance in km
      return distance;
    }
    
    function deg2rad(deg) {
      return deg * (Math.PI/180);
    }
  });