document.addEventListener("DOMContentLoaded", () => {
    fetch("https://api.github.com/repos/Arffornia/Arffornia_Launcher_V.5.2/releases/latest")
      .then(response => response.json())
      .then(data => {
        const tag = data.tag_name; // e.g. v0.1.6
        const version = data.name; // e.g. 0.1.6

        if (!tag || !version) {
          console.warn("Failed to retrieve release version from GitHub.");
          return;
        }

        const baseUrl = `https://github.com/Arffornia/Arffornia_Launcher_V.5.2/releases/download/${tag}`;

        const urls = {
          windows: `${baseUrl}/Arffornia-Launcher-Setup-${version}.exe`,
          mac: `${baseUrl}/Arffornia-Launcher-${version}.dmg`,
          linux: `${baseUrl}/Arffornia-Launcher-${version}.AppImage`
        };

        // Update the dropdown download links
        const platforms = ["windows", "mac", "linux"];
        platforms.forEach(platform => {
          const link = document.querySelector(`a[href="/download/${platform}"]`);
          if (link) {
            link.href = urls[platform];
            // link.setAttribute("target", "_blank"); // optional: open in new tab
          }
        });
      })
      .catch(error => {
        console.error("Error fetching latest GitHub release:", error);
      });
  });
