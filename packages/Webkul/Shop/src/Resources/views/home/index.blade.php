@include('shop::search.index', [
    'home' => true,
    'query' => request()->query('query'),
    'suggestion' => null,
    'params' => request()->only(['sort', 'limit', 'mode']),
])
