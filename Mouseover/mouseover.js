const objects= document.querySelectorAll('.objet');

objects.forEach(objet =>{
    objet.addEventListener('mouseover', () => {
        objet.style.width = '200px';
        objet.style.height = '200px';    
    })
    objet.addEventListener('mouseout', () => {
        objet.style.width = '150px';
        objet.style.height = '150px';
    })
})