const projectListEl = document.querySelector('#project-list');
const articleListEl = document.querySelector('#article-list');
const generatorPanelEl = document.querySelector('#generator');
const generatorResultEl = document.querySelector('#generator-result');

const toggleGenerator = () => {
  generatorPanelEl.classList.toggle('hidden');
  if (!generatorPanelEl.classList.contains('hidden')) {
    generatorPanelEl.scrollIntoView({ behavior: 'smooth' });
  }
};

document.querySelector('#open-generator').addEventListener('click', toggleGenerator);

document.querySelector('#generator-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  generatorResultEl.textContent = 'Generating ideas…';

  const form = event.currentTarget;
  const formData = new FormData(form);

  try {
    const response = await fetch('/api/ask', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        prompt: formData.get('prompt'),
        systemPrompt: formData.get('system'),
      }),
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Unable to generate ideas.');
    }

    const payload = await response.json();
    const choices = payload.data?.choices ?? [];
    if (choices.length === 0) {
      generatorResultEl.textContent = 'No response received from the AI service.';
      return;
    }

    generatorResultEl.textContent = choices[0].message?.content?.trim() ?? 'Empty response.';
  } catch (error) {
    generatorResultEl.textContent = error.message;
  }
});

async function loadProjects() {
  try {
    const response = await fetch('/api/projects');
    if (!response.ok) {
      throw new Error('Could not load projects');
    }
    const payload = await response.json();
    const projects = payload.data?.projects ?? [];

    projectListEl.innerHTML = '';
    projects.forEach((project) => {
      const card = document.createElement('article');
      card.className = 'card';
      card.innerHTML = `
        <h3>${project.title}</h3>
        <p>${project.summary}</p>
        <ul class="tags">${(project.tags || [])
          .map((tag) => `<li>${tag}</li>`)
          .join('')}</ul>
      `;
      projectListEl.appendChild(card);
    });
  } catch (error) {
    projectListEl.innerHTML = `<p class="error">${error.message}</p>`;
  }
}

async function loadArticles() {
  try {
    const response = await fetch('/api/articles');
    if (!response.ok) {
      throw new Error('Could not load articles');
    }
    const payload = await response.json();
    const articles = payload.data?.articles ?? [];

    articleListEl.innerHTML = '';
    if (articles.length === 0) {
      articleListEl.innerHTML = '<p>No articles published yet.</p>';
      return;
    }

    const list = document.createElement('ul');
    list.className = 'article-list';
    articles.forEach((article) => {
      const item = document.createElement('li');
      item.innerHTML = `
        <a href="${article.url}" target="_blank" rel="noreferrer">
          <span class="title">${article.title}</span>
          <span class="meta">${article.publishedAt ?? ''}</span>
        </a>`;
      list.appendChild(item);
    });
    articleListEl.appendChild(list);
  } catch (error) {
    articleListEl.innerHTML = `<p class="error">${error.message}</p>`;
  }
}

loadProjects();
loadArticles();
