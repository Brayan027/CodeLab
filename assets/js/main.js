// CodeLab - Main JS
document.addEventListener('DOMContentLoaded', function() {
    // Animaciones de entrada escalonadas
    const cards = document.querySelectorAll('.animate-in');
    cards.forEach((card, i) => {
        card.style.animationDelay = (i * 0.1) + 's';
    });
});
