<?phpnamespace App\Controllers;use App\Core\TemplateEngineFactory;class WelcomeController extends Controller{    public function index()    {        print_r($this);        $data = [            'content' => 'home content',            'title' => 'home page',        ];        return TemplateEngineFactory::getInstance()->compile('home', $data);    }    public function create()    {    }    public function store()    {    }    public function show($id)    {    }    public function edit($id)    {    }    public function update($id)    {    }    public function destroy($id)    {    }}