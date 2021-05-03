import React from "react";
import AppSearchAPIConnector from "@elastic/search-ui-app-search-connector";
import {
  ErrorBoundary,
  Facet,
  SearchProvider,
  SearchBox,
  Results,
  PagingInfo,
  ResultsPerPage,
  Paging,
  Sorting,
  WithSearch
} from "@elastic/react-search-ui";
import { Layout } from "@elastic/react-search-ui-views";
import "./components/Common/styles/styles.css";
import {
  buildAutocompleteQueryConfig,
  buildFacetConfigFromConfig,
  buildSearchOptionsFromConfig,
  buildSortOptionsFromConfig,
  getConfig,
  getFacetFields,
  getFacetsNiceName
} from "./config/config-helper";
import ResultView from "./ResultView";

const { hostIdentifier, searchKey, endpointBase, engineName } = getConfig();
const connector = new AppSearchAPIConnector({
  searchKey,
  engineName,
  hostIdentifier,
  endpointBase
});
const config = {
  searchQuery: {
    facets: buildFacetConfigFromConfig(),
    ...buildSearchOptionsFromConfig()
  },
  autocompleteQuery: buildAutocompleteQueryConfig(),
  apiConnector: connector,
  alwaysSearchOnInitialLoad: true
};

const eap_fields = JSON.parse('{"body":{"label":"Body","field_id":"body","type":"text"},"changed":{"label":"Changed","field_id":"changed","type":"text"},"created":{"label":"Authored on","field_id":"created","type":"text"},"field_article_type":{"label":"Article Source","field_id":"field_article_type","type":"text"},"field_blog_section":{"label":"Section","field_id":"field_blog_section","type":"text"},"field_blog_topic":{"label":"Blog Topic","field_id":"field_blog_topic","type":"text"},"field_tax_article_type":{"label":"Article Type","field_id":"field_tax_article_type","type":"text"},"field_tax_content_format":{"label":"Content Format","field_id":"field_tax_content_format","type":"text"},"field_tax_disease":{"label":"Disease","field_id":"field_tax_disease","type":"text"},"field_tax_event_type":{"label":"Event Type","field_id":"field_tax_event_type","type":"text"},"field_tax_ga_type":{"label":"Grant Award Type","field_id":"field_tax_ga_type","type":"text"},"field_tax_research_type":{"label":"Research Type","field_id":"field_tax_research_type","type":"text"},"field_tax_topic":{"label":"Topic","field_id":"field_tax_topic","type":"text"},"path":{"label":"URL alias","field_id":"path","type":"text"},"status":{"label":"Published","field_id":"status","type":"text"},"title":{"label":"Title","field_id":"title","type":"text"},"type":{"label":"Content type","field_id":"type","type":"text"}}')

export default function App() {
  return (
    <SearchProvider config={config}>
      <WithSearch mapContextToProps={({ wasSearched }) => ({ wasSearched })}>
        {({ wasSearched }) => {
          return (
            <div className="App">
              <ErrorBoundary>
                <Layout
                  header={
                  <div className="sui__header-wrapper">
                    <SearchBox autocompleteSuggestions={true} />
                    <div>
                      {wasSearched && (
                          <Sorting
                            sortOptions={buildSortOptionsFromConfig()}
                          />
                        )}
                    </div>
                  </div>
                }
                  sideContent={
                    <div>
                      {getFacetFields().map(field => (
                        <Facet key={field} field={field} label={getFacetsNiceName()[field]['label']} />
                      ))}
                    </div>
                  }
                  bodyContent={
                    <Results
                      titleField={getConfig().titleField}
                      urlField={getConfig().urlField}
                      resultView={ResultView}
                      shouldTrackClickThrough={true}
                    />
                  }
                  bodyHeader={
                    <React.Fragment>
                      {wasSearched && <PagingInfo />}
                      {wasSearched && <ResultsPerPage />}
                    </React.Fragment>
                  }
                  bodyFooter={<Paging />}
                />
              </ErrorBoundary>
            </div>
          );
        }}
      </WithSearch>
    </SearchProvider>
  );
}