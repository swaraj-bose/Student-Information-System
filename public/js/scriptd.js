// scripts.js

document.addEventListener('DOMContentLoaded', () => {

  // 1) Confirm before destructive actions (e.g. delete question)
  document.querySelectorAll('.actions a[href*="delete_question"]').forEach(link => {
    link.addEventListener('click', e => {
      if (!confirm('Are you sure you want to delete this question?')) {
        e.preventDefault();
      }
    });
  });

  // 2) Client-side validation for posting a question
  const postForm = document.querySelector('form[action*="post.php"]');
  if (postForm && postForm.querySelector('textarea[name="content"]')) {
    postForm.addEventListener('submit', e => {
      const txt = postForm.querySelector('textarea[name="content"]').value.trim();
      if (!txt) {
        alert('Question cannot be empty.');
        e.preventDefault();
      }
    });
  }

  // 3) AJAX for reactions (like/dislike)
  document.querySelectorAll('form[action*="reaction.php"]').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();

      const formData = new FormData(form);
      const button = form.querySelector('button');

      // Disable button temporarily to prevent double clicks
      if (button) button.disabled = true;

      fetch(form.action, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(res => {
        if (!res.ok) throw new Error('Network error');
        return res.text();
      })
      .then(() => {
        // Optional: Improve UX by not reloading the entire page
        // Instead of reloading, just update count via DOM (you can implement that)
        window.location.reload();
      })
      .catch(() => {
        alert('Could not record reaction.');
      })
      .finally(() => {
        if (button) button.disabled = false;
      });
    });
  });

  // 4) (Optional) Stub for tagging autocomplete
  const tagInputs = document.querySelectorAll('input[name="tagged_username"]');
  tagInputs.forEach(input => {
    input.addEventListener('input', () => {
      const query = input.value.trim();
      if (query.length > 1) {
        // You could fetch(`../src/user_search.php?q=${encodeURIComponent(query)}`)
        // .then(...) to show suggestions
      }
    });
  });

});

