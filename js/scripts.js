let currentCategory = 'all'; // Track active category

function toggleMenu(postId) {
    const menu = document.getElementById(`menu-${postId}`);
    menu.classList.toggle('active');
}

function toggleCommentMenu(commentId) {
    const menu = document.getElementById(`comment-menu-${commentId}`);
    menu.classList.toggle('active');
}

function filterPosts(category) {
    currentCategory = category;
    const posts = document.querySelectorAll('.post');
    const buttons = document.querySelectorAll('.category-btn');

    // Update active button
    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === category || (category === 'all' && btn.textContent === 'Todos')) {
            btn.classList.add('active');
        }
    });

    // Filter posts
    posts.forEach(post => {
        if (category === 'all' || post.dataset.category === category) {
            post.classList.remove('hidden');
        } else {
            post.classList.add('hidden');
        }
    });

    // Reset page for load more
    currentPage = 1;
    document.getElementById('feed').innerHTML = '';
    loadMorePosts();
}

function editPost(postId) {
    const contentElement = document.getElementById(`post-content-${postId}`);
    const originalContent = contentElement.dataset.raw || contentElement.innerText;
    const categoryElement = document.querySelector(`[data-post-id="${postId}"] p small`);
    const originalCategory = categoryElement ? categoryElement.innerText.replace('Categoria: ', '') : '';

    const textarea = document.createElement('textarea');
    textarea.value = originalContent;
    contentElement.replaceWith(textarea);

    const select = document.createElement('select');
    select.name = 'category';
    const categories = ['Receitas Doces', 'Receitas Salgadas', 'Dicas de Cozinha', 'Receitas Veganas'];
    select.innerHTML = '<option value="" disabled>Selecione uma categoria</option>' +
        categories.map(cat => `<option value="${cat}" ${cat === originalCategory ? 'selected' : ''}>${cat}</option>`).join('');
    textarea.after(select);

    const saveButton = document.createElement('button');
    saveButton.innerText = 'Salvar';
    saveButton.onclick = function() {
        const newContent = textarea.value;
        const newCategory = select.value;
        fetch('update_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `post_id=${postId}&content=${encodeURIComponent(newContent)}&category=${encodeURIComponent(newCategory)}`
        }).then(() => location.reload());
    };
    select.after(saveButton);

    textarea.dataset.raw = originalContent;
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
    const originalContent = contentElement.dataset.raw || contentElement.innerText;
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

    textarea.dataset.raw = originalContent;
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
                <div class="comment-content" id="comment-content-${data.comment_id}" data-raw="${content}">${content}</div>
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
    const url = currentCategory === 'all' ? `load_more.php?page=${currentPage}` : `load_more.php?page=${currentPage}&category=${encodeURIComponent(currentCategory)}`;
    fetch(url)
        .then(response => response.text())
        .then(html => {
            document.getElementById('feed').insertAdjacentHTML('beforeend', html);
        })
        .catch(error => console.error('Error loading more posts:', error));
}

document.addEventListener('click', function(event) {
    const menus = document.querySelectorAll('.options-menu');
    menus.forEach(menu => {
        if (!event.target.closest('.post-options')) {
            menu.classList.remove('active');
        }
    });
});

function filterPosts(category) {
    const posts = document.querySelectorAll('.post');
    const buttons = document.querySelectorAll('.category-btn');
    const feed = document.getElementById('feed');
    
    // Atualiza botões ativos
    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent === category || (category === 'all' && btn.textContent === 'Todos')) {
            btn.classList.add('active');
        }
    });
    
    let visiblePosts = 0;
    
    // Filtra posts
    posts.forEach(post => {
        if (category === 'all') {
            post.style.display = 'block';
            visiblePosts++;
        } else {
            const postCategory = post.getAttribute('data-category');
            if (postCategory === category) {
                post.style.display = 'block';
                visiblePosts++;
            } else {
                post.style.display = 'none';
            }
        }
    });
    
    // Remove mensagem anterior se existir
    const existingMessage = document.querySelector('.no-posts-filter-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Remove mensagem de "nenhum post" original se existir
    const noPostsMessage = document.querySelector('.no-posts-message');
    if (noPostsMessage) {
        noPostsMessage.style.display = 'none';
    }
    
    // Adiciona mensagem se não houver posts visíveis
    if (visiblePosts === 0 && category !== 'all') {
        const message = document.createElement('div');
        message.className = 'no-posts-filter-message';
        message.innerHTML = `<p>Não há posts na categoria "${category}".</p>`;
        feed.appendChild(message);
    }
}

function toggleMenu(postId) {
    const menu = document.getElementById(`menu-${postId}`);
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function toggleCommentMenu(commentId) {
    const menu = document.getElementById(`comment-menu-${commentId}`);
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Fecha menus quando clicar fora deles
document.addEventListener('click', function(e) {
    if (!e.target.matches('.options-btn')) {
        const menus = document.querySelectorAll('.options-menu');
        menus.forEach(menu => {
            menu.style.display = 'none';
        });
    }
});
