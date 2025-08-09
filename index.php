
<?php include_once 'affichage/_debut.inc.php'; ?>

<?php include_once 'affichage/hero.affichage.php'; ?>

<?php include_once 'affichage/services.affichage.php'; ?>

<?php include_once 'affichage/vehicule.affichage.php'; ?>

<?php include_once 'affichage/contact.affichage.php'; ?>

<?php include_once 'affichage/_fin.inc.php'; ?>


  <script>
    // Année courante
    document.getElementById('year').textContent = new Date().getFullYear();
    // Défilement doux
    document.querySelectorAll('a[href^="#"]').forEach(a=>a.addEventListener('click',e=>{
      const id=a.getAttribute('href').slice(1);
      const el=document.getElementById(id);
      if(el){e.preventDefault();el.scrollIntoView({behavior:'smooth',block:'start'});}
    }));
  </script>