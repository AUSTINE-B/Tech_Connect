
document.addEventListener("DOMContentLoaded", () => {
    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");

    const searchForm = document.getElementById("technicianSearch");
    const serviceSelect = document.getElementById("serviceSelect");
    const locationSelect = document.getElementById("locationSelect");
    const technicianCards = Array.from(
        document.querySelectorAll(".technician-card")
    );

    const searchMessage = document.getElementById("searchMessage");
    const noResults = document.getElementById("noResults");
    const technicianGrid = document.getElementById("technicianGrid");
    const resetSearch = document.getElementById("resetSearch");

    // Mobile navigation
    menuToggle.addEventListener("click", () => {
        const isOpen = mainNav.classList.toggle("open");

        menuToggle.setAttribute("aria-expanded", String(isOpen));
        menuToggle.setAttribute(
            "aria-label",
            isOpen ? "Close navigation" : "Open navigation"
        );
        menuToggle.textContent = isOpen ? "✕" : "☰";
    });

    // Close mobile navigation after choosing a section
    mainNav.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            mainNav.classList.remove("open");
            menuToggle.setAttribute("aria-expanded", "false");
            menuToggle.setAttribute("aria-label", "Open navigation");
            menuToggle.textContent = "☰";
        });
    });

    // Filter sample technician profiles
    function filterTechnicians() {
        const selectedService = serviceSelect.value.trim().toLowerCase();
        const selectedLocation = locationSelect.value.trim().toLowerCase();

        let visibleCount = 0;

        technicianCards.forEach((card) => {
            const cardService = card.dataset.service.toLowerCase();
            const cardLocation = card.dataset.location.toLowerCase();

            const matchesService =
                !selectedService || cardService === selectedService;

            const matchesLocation =
                !selectedLocation ||
                cardLocation.includes(selectedLocation);

            const isVisible = matchesService && matchesLocation;

            card.hidden = !isVisible;

            if (isVisible) {
                visibleCount++;
            }
        });

        noResults.hidden = visibleCount !== 0;
        technicianGrid.hidden = visibleCount === 0;

        if (!selectedService && !selectedLocation) {
            searchMessage.textContent = "";
        } else {
            searchMessage.textContent =
                `${visibleCount} sample technician(s) found.`;
        }

        document.getElementById("technicians").scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    }

    searchForm.addEventListener("submit", (event) => {
        event.preventDefault();
        filterTechnicians();
    });

    // Clicking a popular service selects it in the search form
    document.querySelectorAll(".service-card").forEach((card) => {
        card.addEventListener("click", () => {
            serviceSelect.value = card.dataset.service;
            filterTechnicians();
        });
    });

    // Reset search filters
    resetSearch.addEventListener("click", () => {
        serviceSelect.value = "";
        locationSelect.value = "";

        technicianCards.forEach((card) => {
            card.hidden = false;
        });

        technicianGrid.hidden = false;
        noResults.hidden = true;
        searchMessage.textContent = "";

        document.getElementById("search").scrollIntoView({
            behavior: "smooth"
        });
    });
    // Find Technicians page filters
    const listingSearch = document.getElementById("listingSearch");

    if (listingSearch) {
        const serviceFilter = document.getElementById("listingService");
        const locationFilter = document.getElementById("listingLocation");
        const ratingFilter = document.getElementById("listingRating");
        const listingCards = Array.from(
            document.querySelectorAll(".listing-card")
        );

        const listingCount = document.getElementById("listingCount");
        const listingEmpty = document.getElementById("listingEmpty");
        const listingGrid = document.getElementById("listingGrid");
        const listingReset = document.getElementById("listingReset");

        function updateListing() {
            let visibleCount = 0;

            listingCards.forEach((card) => {
                const matchesService =
                    !serviceFilter.value ||
                    card.dataset.service === serviceFilter.value;

                const matchesLocation =
                    !locationFilter.value ||
                    card.dataset.location === locationFilter.value;

                const matchesRating =
                    Number(card.dataset.rating) >= Number(ratingFilter.value);

                const visible =
                    matchesService && matchesLocation && matchesRating;

                card.hidden = !visible;

                if (visible) {
                    visibleCount++;
                }
            });

            listingCount.textContent =
                `${visibleCount} sample technician(s) found`;

            listingEmpty.hidden = visibleCount > 0;
            listingGrid.hidden = visibleCount === 0;
        }

        listingSearch.addEventListener("submit", (event) => {
            event.preventDefault();
            updateListing();
        });

        listingReset.addEventListener("click", () => {
            serviceFilter.value = "";
            locationFilter.value = "";
            ratingFilter.value = "0";
            updateListing();
        });
    }

    
    // Technician details: demo booking form
    const bookingForm = document.getElementById("bookingForm");

    if (bookingForm) {
        const dateInput = document.getElementById("bookingDate");
        const bookingMessage = document.getElementById("bookingMessage");

        // Prevent selection of dates before today.
        const today = new Date();
        const localToday = [
            today.getFullYear(),
            String(today.getMonth() + 1).padStart(2, "0"),
            String(today.getDate()).padStart(2, "0")
        ].join("-");

        dateInput.min = localToday;

        bookingForm.addEventListener("submit", (event) => {
            event.preventDefault();

            if (!bookingForm.reportValidity()) {
                return;
            }

            bookingMessage.textContent =
                "Demo only: your booking details were not sent or saved. " +
                "The backend will be connected later.";

            bookingMessage.style.color = "#0866f5";
        });
    }

});