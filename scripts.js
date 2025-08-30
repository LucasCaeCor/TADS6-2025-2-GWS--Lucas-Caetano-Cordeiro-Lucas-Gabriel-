// Updated scripts.js - Added functions for dropdown, dynamic add/delete comment, delete post

function toggleMenu(postId) {
    const menu = document.getElementById(`menu-${postId}`);
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function toggleCommentMenu(commentId) {
    const menu = document.getElementById(`comment-menu-${commentId}`);
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function editPost(postId) {
    const contentElement = document.getElementById(`post-content-${postId}`);
    const originalContent = contentElement.innerText;
    const textarea = document.createElement('textarea');
    textarea.value = originalContent;
    contentElement.replaceWith(textarea);

    const saveButton = document.createElement('button');
    saveButton.innerText = 'Salvar';
    saveButton.onclick = function() {
        const newContent = textarea.value;
        fetch('update_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${postId}&content=${encodeURIComponent(newContent)}`
        }).then(() => location.reload());
    };
    textarea.after(saveButton);
}

function deletePost(postId) {
    if (confirm('Tem certeza que deseja deletar este post?')) {
        fetch('delete_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${postId}`
        }).then(() => {
            const postElement = document.querySelector(`[data-post-id="${postId}"]`);
            postElement.remove();
        });
    }
}

function editComment(commentId) {
    const contentElement = document.getElementById(`comment-content-${commentId}`);
    const originalContent = contentElement.innerText;
    const textarea = document.createElement('textarea');
    textarea.value = originalContent;
    contentElement.replaceWith(textarea);

    const saveButton = document.createElement('button');
    saveButton.innerText = 'Salvar';
    saveButton.onclick = function() {
        const newContent = textarea.value;
        fetch('update_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `comment_id=${commentId}&content=${encodeURIComponent(newContent)}`
        }).then(() => location.reload());
    };
    textarea.after(saveButton);
}

function addComment(event, postId) {
    event.preventDefault();
    const form = event.target;
    const content = form.comment_content.value;

    fetch('add_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&content=${encodeURIComponent(content)}`
    }).then(response => response.json())
      .then(data => {
        if (data.success) {
            const commentsDiv = document.getElementById(`comments-${postId}`);
            const newComment = document.createElement('div');
            newComment.classList.add('comment');
            newComment.dataset.commentId = data.comment_id;
            newComment.innerHTML = `
                <div class="comment-header">
                    <img src="${data.profile_photo}" alt="Foto de perfil" class="profile-photo">
                    <div>
                        <a href="profile.php?user_id=${data.user_id}"><strong>${data.username}</strong></a>
                        <span> - Agora</span>
                    </div>
                    <div class="post-options">
                        <button class="options-btn" onclick="toggleCommentMenu(${data.comment_id})">...</button>
                        <div id="comment-menu-${data.comment_id}" class="options-menu">
                            <button onclick="editComment(${data.comment_id})">Editar</button>
                            <button onclick="deleteComment(${data.comment_id}, ${postId})">Deletar</button>
                        </div>
                    </div>
                </div>
                <p id="comment-content-${data.comment_id}">${content}</p>
            `;
            commentsDiv.prepend(newComment);
            form.comment_content.value = '';
        }
    });
}

function deleteComment(commentId, postId) {
    if (confirm('Tem certeza que deseja deletar este comentário?')) {
        fetch('delete_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `comment_id=${commentId}`
        }).then(() => {
            const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
            commentElement.remove();
        });
    }
}

function loadMorePosts() {
    currentPage++;
    fetch(`load_more.php?page=${currentPage}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('feed').insertAdjacentHTML('beforeend', html);
        })
        .catch(error => console.error('Error loading more posts:', error));
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const menus = document.querySelectorAll('.options-menu');
    menus.forEach(menu => {
        if (!event.target.closest('.post-options')) {
            menu.style.display = 'none';
        }
    });
});