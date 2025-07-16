let sliderWrapper = document.querySelector(".slider-wrapper");
    let totalSlides = document.querySelectorAll(".slider-wrapper > div").length;
    let currentIndex = 0;
    function showNextSlide() {
        currentIndex = (currentIndex + 1) % totalSlides;
        sliderWrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
    }
    setInterval(showNextSlide, 3000);
    const prevButton = document.querySelector(".prev-btn");
    const nextButton = document.querySelector(".next-btn");
    const carousel = document.querySelector(".carousel-container");
    const scrollAmount = 200;
    prevButton.addEventListener("click", function () {
      carousel.scrollBy({
        left: -scrollAmount, 
        behavior: "smooth", 
      });
    });
    nextButton.addEventListener("click", function () {
      carousel.scrollBy({
        left: scrollAmount, 
        behavior: "smooth", 
      });
    });