document.addEventListener("DOMContentLoaded", () => {

    const buttons = document.querySelectorAll(".filter-btn");
    const cards = document.querySelectorAll(".card");

    buttons.forEach(btn => {

        btn.addEventListener("click", () => {

            // active state
            buttons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const filter = btn.dataset.filter;

            cards.forEach(card => {

                const status = card.querySelector(".status").classList.contains("answered")
                    ? "answered"
                    : "pending";

                if(filter === "all" || filter === status){
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }

            });

        });

    });

});