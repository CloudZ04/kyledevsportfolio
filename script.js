// Sticky header on scroll
window.addEventListener('scroll', () => {
    const header = document.querySelector('header');
    if (window.scrollY > 50) {
        header.classList.add('scrolled');
    } else {
        header.classList.remove('scrolled');
    }
});

// Details section tabs (Experience/Education)
const detailsTabButtons = document.querySelectorAll('#details .tab-btn');
const detailsTabContents = document.querySelectorAll('#details .tab-content');

detailsTabButtons.forEach(button => {
    button.addEventListener('click', () => {
        detailsTabButtons.forEach(btn => btn.classList.remove('active'));
        detailsTabContents.forEach(content => content.classList.remove('active'));
        
        button.classList.add('active');
        
        const tabName = button.getAttribute('data-tab');
        document.getElementById(`${tabName}-content`).classList.add('active');
    });
});

// Project description "View More" functionality
function initViewMore() {
    const wrappers = document.querySelectorAll('.project-description-wrapper');
    
    wrappers.forEach(wrapper => {
        const contentArea = wrapper.querySelector('.content-area');
        const button = wrapper.querySelector('.view-more-btn');
        
        if (!contentArea || !button) return;
        
        // Reset state
        contentArea.classList.remove('expanded');
        button.classList.remove('show');
        button.textContent = 'View More';
        
        // Check if content is taller than 5 lines (scrollHeight > clientHeight means it's being cut off)
        if (contentArea.scrollHeight > contentArea.clientHeight) {
            button.classList.add('show');
            
            // Remove any existing listener by cloning
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            newButton.addEventListener('click', () => {
                const isExpanded = contentArea.classList.contains('expanded');
                
                if (isExpanded) {
                    contentArea.classList.remove('expanded');
                    newButton.textContent = 'View More';
                } else {
                    contentArea.classList.add('expanded');
                    newButton.textContent = 'View Less';
                }
            });
        }
    });
}

// NAV SECTION TOGGLING + STATE PERSISTENCE VIA URL HASH

const navLinks = document.querySelectorAll("nav a");

function showSection(targetId) {
  // Get all sections dynamically each time
  const allSections = document.querySelectorAll("main > section");
  
  // HOME → show everything (including FAQ)
  if (targetId === "about") {
    allSections.forEach(section => {
      section.style.display = "";
    });
    return;
  }

  // OTHER SECTIONS → show only target
  allSections.forEach(section => {
    section.style.display = "none";
  });

  const targetSection = document.getElementById(targetId);
  if (targetSection) {
    targetSection.style.display = "block";
  }
}

// Handle nav clicks
navLinks.forEach(link => {
  link.addEventListener("click", e => {
    e.preventDefault();

    const targetId = link.getAttribute("href").replace("#", "");

    // Update URL hash (this is what persists state)
    window.location.hash = targetId;

    showSection(targetId);
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
});

// Restore state on page load / refresh
window.addEventListener("load", () => {
  const hash = window.location.hash.replace("#", "");

  if (hash) {
    showSection(hash);
  } else {
    // Default state = home
    showSection("about");
  }
});

// Handle back / forward browser buttons
window.addEventListener("hashchange", () => {
  const hash = window.location.hash.replace("#", "");
  showSection(hash || "about");
});


function initFAQ() {
    document.querySelectorAll('.faq-question').forEach(button => {
        button.addEventListener('click', () => {
            const faqItem = button.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close all FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Open clicked item if it wasn't active
            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });
};

// After loading FAQ via fetch:
document.addEventListener('DOMContentLoaded', function() {
  console.log('Starting component loading...');
  
  // Load FAQ
  fetch('faq.html')
    .then(response => {
      console.log('FAQ fetch response:', response.ok, response.status);
      if (!response.ok) throw new Error('Failed to load FAQ');
      return response.text();
    })
    .then(data => {
      console.log('FAQ data loaded, length:', data.length);
      const faqSection = document.getElementById('faq');
      faqSection.innerHTML = data;
      console.log('FAQ inserted into DOM');
      initFAQ();
      
      // Apply current navigation state after FAQ loads
      const hash = window.location.hash.replace("#", "");
      const currentSection = hash || "about";
      
      // Hide FAQ if we're not on the FAQ page
      if (currentSection !== "faq" && currentSection !== "about") {
        faqSection.style.display = "none";
      }
    })
    .catch(error => {
      console.error('Error loading FAQ:', error);
    });
  
  // Load Projects
  fetch('projects.html')
    .then(response => {
      console.log('Projects fetch response:', response.ok, response.status);
      if (!response.ok) throw new Error('Failed to load Projects');
      return response.text();
    })
    .then(data => {
      console.log('Projects data loaded, length:', data.length);
      const projectsSection = document.getElementById('projects');
      // Resolve asset paths relative to current page so images load after fetch inject
      const base = window.location.pathname.replace(/\/[^/]*$/, '') || '';
      const resolved = data.replace(/src="\.\/assets\//g, `src="${base}/assets/`);
      projectsSection.innerHTML = resolved;
      console.log('Projects inserted into DOM');
      
      // Re-initialize project tabs and view more buttons
      const projectTabButtons = document.querySelectorAll('.project-tab-btn');
      const projectTabContents = document.querySelectorAll('.project-tab-content');
      
      projectTabButtons.forEach(button => {
        button.addEventListener('click', () => {
          projectTabButtons.forEach(btn => btn.classList.remove('active'));
          projectTabContents.forEach(content => content.classList.remove('active'));
          
          button.classList.add('active');
          
          const tabName = button.getAttribute('data-tab');
          document.getElementById(`${tabName}-content`).classList.add('active');
          
          // Re-initialize view more buttons after tab switch
          setTimeout(initViewMore, 100);
        });
      });
      
      // Initialize view more buttons for projects
      initViewMore();
      
      // Apply current navigation state
      const hash = window.location.hash.replace("#", "");
      const currentSection = hash || "about";
      
      if (currentSection !== "projects" && currentSection !== "about") {
        projectsSection.style.display = "none";
      }
    })
    .catch(error => {
      console.error('Error loading Projects:', error);
    });
  
  // Load Contact
  fetch('contact.html')
    .then(response => {
      console.log('Contact fetch response:', response.ok, response.status);
      if (!response.ok) throw new Error('Failed to load Contact');
      return response.text();
    })
    .then(data => {
      console.log('Contact data loaded, length:', data.length);
      const contactSection = document.getElementById('contact');
      contactSection.innerHTML = data;
      console.log('Contact inserted into DOM');
      
      const form = document.querySelector('.contact-form');
      const messageDiv = document.getElementById('form-message');
      
      if (window.location.protocol === 'file:') {
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          messageDiv.textContent = 'Please open this site via http://localhost/portfolio-site/ to use the contact form!';
          messageDiv.className = 'form-message error';
          messageDiv.style.display = 'block';
        });
      } else {
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          const submitBtn = form.querySelector('.submit-btn');
          const originalText = submitBtn.textContent;
          submitBtn.disabled = true;
          submitBtn.textContent = 'Sending...';
          messageDiv.style.display = 'none';
          
          const formData = new FormData(form);
          fetch('contact.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                messageDiv.textContent = 'Message sent successfully! I\'ll get back to you soon.';
                messageDiv.className = 'form-message success';
                form.reset();
              } else {
                messageDiv.textContent = 'Oops! Something went wrong. Please try again or email me directly.';
                messageDiv.className = 'form-message error';
              }
              messageDiv.style.display = 'block';
            })
            .catch(() => {
              messageDiv.textContent = 'Oops! Something went wrong. Please try again or email me directly.';
              messageDiv.className = 'form-message error';
              messageDiv.style.display = 'block';
            })
            .finally(() => {
              submitBtn.disabled = false;
              submitBtn.textContent = originalText;
            });
        });
      }
      
      // Apply current navigation state
      const hash = window.location.hash.replace("#", "");
      const currentSection = hash || "about";
      
      if (currentSection !== "contact" && currentSection !== "about") {
        contactSection.style.display = "none";
      }
    })
    .catch(error => {
      console.error('Error loading Contact:', error);
    });
});