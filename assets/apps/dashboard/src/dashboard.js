import './style.scss';
import App from './Components/App';
import { registerStore } from '@wordpress/data';
import { render } from '@wordpress/element';

import actions from './store/actions';
import reducer from './store/reducer';
import selectors from './store/selectors';
import '../../../js/src/tracking.js';

registerStore('neve-dashboard', {
	reducer,
	actions,
	selectors,
});

const Root = () => <App />;
render(<Root />, document.getElementById('neve-dashboard'));
