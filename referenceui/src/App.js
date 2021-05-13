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
  getFacetDisjunctives,
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
    disjunctiveFacets: getFacetDisjunctives(),
    facets: buildFacetConfigFromConfig(),
    ...buildSearchOptionsFromConfig()
  },
  autocompleteQuery: buildAutocompleteQueryConfig(),
  apiConnector: connector,
  alwaysSearchOnInitialLoad: true
};

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
                        <Facet 
                          filterType="any"
                          isFilterable={field.isFilterable}
                          key={field.field}
                          field={field.field}
                          label={field.title} 
                        />
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