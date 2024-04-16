import * as skinview3d from "skinview3d";

const elements = document.getElementsByClassName("skin_viewer");

function rdFromRange(min, max) {
  return Math.random() * (max - min) + min;
}

async function checkMinecraftUsername(username) {
  const url = `https://mineskin.eu/skin/${username}`;
  
  try {
      const response = await fetch(url);
      return response.ok;
  } catch (error) {
      console.error("Erreur lors de la requête :", error);
      return false; 
  }
  
  return false;
}

(async () => {
  Array.from(elements).forEach(async element => {
      var username = element.getAttribute('data-username');
      const isValidUsername = await checkMinecraftUsername(username);
      if (!isValidUsername) {
        username = "Steve";
      }

      const viewer = new skinview3d.SkinViewer({
        canvas: element,
        width: 200,
        height: 300,
        skin: `https://mineskin.eu/skin/${username}`,
        enableControls: true
      });

      viewer.animation = new skinview3d.WalkingAnimation();
      viewer.animation.speed = rdFromRange(0.35, 0.6);
      viewer.animation.progress = rdFromRange(0, 100);
      viewer.controls.enabled = false;
      // viewer.autoRotate = true;
      // viewer.autoRotateSpeed = 0.5;
  });
})();
